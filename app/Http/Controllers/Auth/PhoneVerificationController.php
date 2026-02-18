<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Services\OtpService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Phone OTP verification. Primary account activation method.
 */
class PhoneVerificationController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * Show the phone verification form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterVerification($user);
        }

        return view('auth.verify-phone');
    }

    /**
     * Verify the OTP.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterVerification($user);
        }

        if (! $this->otpService->verifyOtp($user, $request->input('otp'))) {
            throw ValidationException::withMessages([
                'otp' => [__('The verification code is invalid or has expired.')],
            ]);
        }

        $user->update(['phone_verified_at' => now()]);

        return $this->redirectAfterVerification($user);
    }

    /**
     * Resend OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterVerification($user);
        }

        $result = $this->otpService->sendOtp($user);

        if ($result['success']) {
            return back()->with('status', $result['message']);
        }

        return back()->withErrors(['otp' => $result['message']]);
    }

    /**
     * Redirect after successful verification based on membership type.
     */
    protected function redirectAfterVerification(User $user): RedirectResponse
    {
        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])) {
            return redirect()->route('approval.pending');
        }

        if (config('app.email_verification_enabled', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
