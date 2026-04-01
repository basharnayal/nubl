<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\NotificationServiceInterface;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Services\OtpService;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Rules\SaudiPhoneNumber;
use App\Rules\SaudiPhoneUnique;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Provider registration: 4-step form, status=pending_approval until admin approves.
 * Flow: /register → select Provider → link to /register/provider → submit → approval-pending
 */
class ProviderRegistrationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        // If provider already submitted, show read-only view
        if (auth()->check() && auth()->user()->providerProfile) {
            return $this->showApplication($request);
        }

        return view('auth.register-provider', [
            'providerData' => null,
            'businessCategories' => config('provider.business_categories'),
            'serviceTypes' => config('provider.service_types'),
            'weekdays' => config('provider.weekdays'),
            'documentMaxSizeMb' => config('provider.document_max_size_mb', 5),
        ]);
    }

    /** Read-only view for providers who already submitted (awaiting approval) */
    public function showApplication(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user?->providerProfile) {
            return redirect()->route('register.provider');
        }

        return view('auth.provider-application', [
            'providerData' => [
                'profile' => $user->providerProfile,
                'operating' => $user->providerOperatingInfo,
                'financial' => $user->providerFinancialInfo,
                'documents' => $user->providerDocuments,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxMb = config('provider.document_max_size_mb', 5);
        $maxBytes = $maxMb * 1024 * 1024;

        $validated = $request->validate([
            // Step 1
            'full_name_ar' => ['required', 'string', 'max:255'],
            'full_name_en' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'business_name_ar' => ['required', 'string', 'max:255'],
            'business_name_en' => ['required', 'string', 'max:255'],
            'unified_number' => ['required', 'string', 'max:50'],
            'business_category' => ['required', 'array'],
            'business_category.*' => ['string', 'in:'.implode(',', config('provider.business_categories'))],
            'address_ar' => ['required', 'string', 'max:1000'],
            'address_en' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.cities', [])))],
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.regions', [])))],
            'location' => ['nullable', 'string', 'max:500'],
            // Step 2: operating_hours validated below (per-day structure)
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'service_type' => ['required', 'array'],
            'service_type.*' => ['string', 'in:'.implode(',', config('provider.service_types'))],
            'estimated_preparation_order_time' => ['required', 'string', 'max:100'],
            'adoption_support' => ['required', 'string', 'in:yes,partially,no'],
            // Step 3
            'bank_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:50'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            // Step 4
            'password' => ['required', Rules\Password::defaults()],
            'business_license' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
            'id_or_iqama' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
        ]);

        // Build operating_hours JSON (each day: closed OR open+close)
        $operatingHours = [];
        $weekdays = array_keys(config('provider.weekdays'));
        $request->validate([
            'operating_hours' => ['required', 'array'],
            ...collect($weekdays)->mapWithKeys(fn ($d) => ["operating_hours.{$d}" => ['required', 'array']])->all(),
        ]);
        $oh = $request->input('operating_hours', []);
        foreach ($weekdays as $day) {
            $dayData = $oh[$day] ?? [];
            $closed = filter_var($dayData['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                $operatingHours[$day] = ['closed' => true];
            } else {
                $open = trim($dayData['open'] ?? '');
                $close = trim($dayData['close'] ?? '');
                if (! $open || ! $close) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "operating_hours.{$day}" => [__('Set opening and closing time, or mark as closed.')],
                    ]);
                }
                $operatingHours[$day] = ['open' => $open, 'close' => $close, 'closed' => false];
            }
        }

        $licensePath = $request->file('business_license')->store('provider_documents', 'local');
        $idPath = $request->file('id_or_iqama')->store('provider_documents', 'local');

        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        try {
            DB::transaction(function () use ($validated, $phoneNormalized, $operatingHours, $licensePath, $idPath) {
                $user = User::create([
                    'name' => $validated['full_name_en'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'membership_type' => User::MEMBERSHIP_PROVIDER,
                    'status' => User::STATUS_PENDING_APPROVAL,
                    'phone_number' => $phoneNormalized,
                    'accepting_orders' => true,
                ]);

                $user->assignRole('provider');

                ProviderProfile::create([
                    'user_id' => $user->id,
                    'full_name_ar' => $validated['full_name_ar'],
                    'full_name_en' => $validated['full_name_en'],
                    'phone_number' => $phoneNormalized,
                    'email' => $validated['email'],
                    'business_name_ar' => $validated['business_name_ar'],
                    'business_name_en' => $validated['business_name_en'],
                    'unified_number' => $validated['unified_number'],
                    'business_category' => $validated['business_category'],
                    'address_ar' => $validated['address_ar'],
                    'address_en' => $validated['address_en'],
                    'city' => $validated['city'],
                    'region' => $validated['region'],
                    'location' => $validated['location'] ?? null,
                ]);

                ProviderOperatingInfo::create([
                    'user_id' => $user->id,
                    'operating_hours' => $operatingHours,
                    'daily_capacity' => $validated['daily_capacity'],
                    'service_type' => $validated['service_type'],
                    'estimated_preparation_order_time' => $validated['estimated_preparation_order_time'],
                    'adoption_support' => $validated['adoption_support'],
                ]);

                ProviderFinancialInfo::create([
                    'user_id' => $user->id,
                    'bank_name' => $validated['bank_name'],
                    'iban' => $validated['iban'],
                    'account_holder_name' => $validated['account_holder_name'],
                ]);

                ProviderDocuments::create([
                    'user_id' => $user->id,
                    'business_license_path' => $licensePath,
                    'id_or_iqama_path' => $idPath,
                ]);

                event(new Registered($user));

                $this->notificationService->sendNewUserRegisteredToAdmins($user);

                Auth::login($user);

                if (config('app.phone_verification_enabled', true)) {
                    $this->otpService->sendOtp($user);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($licensePath);
            Storage::disk('local')->delete($idPath);
            throw $e;
        }

        if (config('app.phone_verification_enabled', true)) {
            return redirect()->route('verification.phone');
        }
        if (config('app.email_verification_enabled', true) && ! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('approval.pending');
    }
}
