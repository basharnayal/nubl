<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PostAuthRedirector;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OtpService;
use App\Support\PhoneHelper;
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
        private AuditService $auditService,
        private PostAuthRedirector $postAuthRedirector,
    ) {}

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

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp_phone' => ['required', 'string'],
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        $user = $this->otpService->verifyOtpForLogin(
            $request->input('otp_phone'),
            $request->input('otp_code'),
        );

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

        return $this->postAuthRedirector->redirect($user);
    }
}
