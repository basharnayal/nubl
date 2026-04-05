<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\NotificationServiceInterface;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreProviderRegistrationRequest;
use App\Http\Services\AuditService;
use App\Http\Services\OtpService;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Provider registration: 4-step form, status=pending_approval until admin approves.
 * Flow: /register → select Provider → link to /register/provider → submit → approval-pending
 */
class ProviderRegistrationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationServiceInterface $notificationService,
        private AuditService $auditService
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

    public function store(StoreProviderRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $operatingHours = $request->normalizedOperatingHours();

        $licensePath = $request->file('business_license')->store('provider_documents', 'local');
        $idPath = $request->file('id_or_iqama')->store('provider_documents', 'local');

        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        try {
            $user = DB::transaction(function () use ($validated, $phoneNormalized, $operatingHours, $licensePath, $idPath) {
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

                return $user;
            });

            $this->auditService->log('registration', 'completed', [
                'user_id' => $user->id,
                'membership_type' => $user->membership_type,
                'requires_approval' => true,
            ], $user->id);
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
