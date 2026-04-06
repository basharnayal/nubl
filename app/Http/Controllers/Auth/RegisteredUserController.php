<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\NotificationServiceInterface;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Http\Services\AuditService;
use App\Http\Services\OtpService;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Handles /register flow: donor (instant) or recipient (pending approval).
 */
class RegisteredUserController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationServiceInterface $notificationService,
        private AuditService $auditService,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        return $request->validated('membership_type') === User::MEMBERSHIP_DONOR
            ? $this->storeDonor($request)
            : $this->storeRecipient($request);
    }

    /** Donor: instant approval, goes to dashboard */
    protected function storeDonor(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

        $this->notificationService->sendNewUserRegisteredToAdmins($user);

        $this->auditService->log('registration', 'completed', [
            'user_id' => $user->id,
            'membership_type' => $user->membership_type,
            'requires_approval' => false,
        ], $user->id);

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
    protected function storeRecipient(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $idPhotoPath = $this->storeBase64Image($validated['id_photo_base64'], 'recipient_id_photos');
        $addressPhotoPath = $this->storeBase64Image($validated['address_confirmation_base64'], 'recipient_address_photos');

        $phoneNormalized = PhoneHelper::normalize($validated['phone_number']);

        $profileLogoPath = null;

        try {
            $user = DB::transaction(function () use ($request, $validated, $phoneNormalized, $idPhotoPath, $addressPhotoPath, &$profileLogoPath) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'membership_type' => User::MEMBERSHIP_RECIPIENT,
                    'status' => User::STATUS_PENDING_APPROVAL,
                    'phone_number' => $phoneNormalized,
                ]);

                $user->assignRole('recipient');

                if ($request->hasFile('profile_logo')) {
                    $profileLogoPath = $request->file('profile_logo')->store('recipient-logos', 'public');
                }

                RecipientProfile::create([
                    'user_id' => $user->id,
                    'nationality' => $validated['nationality'],
                    'short_address' => $validated['short_address'],
                    'id_type' => $validated['id_type'],
                    'id_photo_path' => $idPhotoPath,
                    'logo_path' => $profileLogoPath,
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
            Storage::disk('local')->delete($idPhotoPath);
            Storage::disk('local')->delete($addressPhotoPath);
            if ($profileLogoPath) {
                Storage::disk('public')->delete($profileLogoPath);
            }
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
