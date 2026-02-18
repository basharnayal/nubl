<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Services\OtpService;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Rules\Base64Image;
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
 * Handles /register flow: donor (instant) or recipient (pending approval).
 */
class RegisteredUserController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Recipient: only base64 camera capture allowed (no file upload)
        if ($request->membership_type === 'recipient') {
            if ($request->hasFile('id_photo') || $request->hasFile('address_confirmation')) {
                abort(422, 'Use camera capture only. File upload is not allowed.');
            }
        }

        $type = $request->validate([
            'membership_type' => ['required', 'in:donor,recipient'],
        ])['membership_type'];

        return $type === User::MEMBERSHIP_DONOR
            ? $this->storeDonor($request)
            : $this->storeRecipient($request);
    }

    /** Donor: instant approval, goes to dashboard */
    protected function storeDonor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'phone_number' => $phoneNormalized,
        ]);

        $user->assignRole('donor');

        event(new Registered($user));

        Auth::login($user);

        if (config('app.phone_verification_enabled', true)) {
            $this->otpService->sendOtp($user);
            return redirect()->route('verification.phone');
        }
        if (config('app.email_verification_enabled', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        return redirect(route('dashboard', absolute: false));
    }

    /** Recipient: pending_approval, goes to approval-pending page until admin approves */
    protected function storeRecipient(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', new SaudiPhoneNumber, new SaudiPhoneUnique],
            'nationality' => ['required', 'string', 'in:'.implode(',', config('nationalities'))],
            'short_address' => ['required', 'string', 'max:500'],
            'id_type' => ['required', 'string', 'in:'.implode(',', RecipientProfile::ID_TYPES)],
            'id_photo_base64' => ['required', 'string', new Base64Image],
            'income_band' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::INCOME_BANDS)],
            'household_size' => ['required', 'integer', 'min:1', 'max:50'],
            'marital_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::MARITAL_STATUSES)],
            'is_student' => ['required', 'boolean'],
            'address_confirmation_base64' => ['required', 'string', new Base64Image],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $idPhotoPath = $this->storeBase64Image($validated['id_photo_base64'], 'recipient_id_photos');
        $addressPhotoPath = $this->storeBase64Image($validated['address_confirmation_base64'], 'recipient_address_photos');

        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        try {
            DB::transaction(function () use ($validated, $phoneNormalized, $idPhotoPath, $addressPhotoPath) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'membership_type' => User::MEMBERSHIP_RECIPIENT,
                    'status' => User::STATUS_PENDING_APPROVAL,
                    'phone_number' => $phoneNormalized,
                ]);

                $user->assignRole('recipient');

                RecipientProfile::create([
                    'user_id' => $user->id,
                    'nationality' => $validated['nationality'],
                    'short_address' => $validated['short_address'],
                    'id_type' => $validated['id_type'],
                    'id_photo_path' => $idPhotoPath,
                ]);

                RecipientKycDetails::create([
                    'user_id' => $user->id,
                    'income_band' => $validated['income_band'],
                    'household_size' => (int) $validated['household_size'],
                    'marital_status' => $validated['marital_status'],
                    'is_student' => (bool) $validated['is_student'],
                    'address_confirmation' => $addressPhotoPath,
                ]);

                event(new Registered($user));

                Auth::login($user);

                if (config('app.phone_verification_enabled', true)) {
                    $this->otpService->sendOtp($user);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($idPhotoPath);
            Storage::disk('local')->delete($addressPhotoPath);
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

    /** Save base64 image to storage, return path. */
    protected function storeBase64Image(string $base64Data, string $directory): string
    {
        preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $base64Data, $matches);
        $extension = $matches[1] ?? 'jpg';
        $extension = $extension === 'jpg' ? 'jpeg' : $extension;

        $base64 = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/', '', $base64Data);
        $decoded = base64_decode($base64, true);

        $filename = uniqid('id_photo_', true).'.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk('local')->put($path, $decoded);

        return $path;
    }
}
