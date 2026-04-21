<?php

namespace App\Http\Controllers\Auth;

use App\Support\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * OTP-based login. Separate flow from email/password login.
 */
class OtpLoginController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private AuditService $auditService
    ) {}

    /**
     * Request OTP for login. User must exist by phone.
     */
    public function requestOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        if (! PhoneHelper::isValid($request->input('phone'))) {
            throw ValidationException::withMessages([
                'otp_phone' => [__('Invalid phone number.')],
            ]);
        }

        $normalized = PhoneHelper::normalize($request->input('phone'));
        $user = User::findByPhone($normalized);

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'otp_phone' => [__('Login failed. Please check your credentials.')],
            ]);
        }

        $result = $this->otpService->sendOtpForLogin($request->input('phone'));

        if ($result['success']) {
            return back()->with('otp_status', $result['message'])
                ->with('otp_phone', $normalized);
        }

        throw ValidationException::withMessages([
            'otp_phone' => [$result['message']],
        ]);
    }

    /**
     * Verify OTP and log in.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_phone' => ['required', 'string'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $phone = $request->input('otp_phone');
        $code = $request->input('otp_code');

        $user = $this->otpService->verifyOtpForLogin($phone, $code);

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'otp_code' => [__('Login failed. Please check your credentials.')],
            ]);
        }

        Auth::login($user, $request->boolean('otp_remember'));
        $request->session()->regenerate();

        $this->auditService->log('auth', 'login', [
            'user_id' => $user->id,
            'method' => 'otp',
        ], $user->id);

        if (config('app.phone_verification_enabled', true) && ! $user->hasVerifiedPhone()) {
            $user->update(['phone_verified_at' => now()]);
        }

        if (config('app.email_verification_enabled', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
