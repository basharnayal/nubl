<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PostAuthRedirector;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OtpService;
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
        private OtpService $otpService,
        private AuditService $auditService,
        private PostAuthRedirector $postAuthRedirector,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterPhoneVerification($user);
        }

        return view('auth.verify-phone');
    }

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
            return $this->redirectAfterPhoneVerification($user);
        }

        if (! $this->otpService->verifyOtp($user, $request->input('otp'))) {
            throw ValidationException::withMessages([
                'otp' => [__('The verification code is invalid or has expired.')],
            ]);
        }

        $user->update(['phone_verified_at' => now()]);

        $this->auditService->log('auth', 'phone_verified', [
            'user_id' => $user->id,
        ], $user->id);

        return $this->redirectAfterPhoneVerification($user);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterPhoneVerification($user);
        }

        $result = $this->otpService->sendOtp($user);

        if ($result['success']) {
            return back()->with('status', $result['message']);
        }

        return back()->withErrors(['otp' => $result['message']]);
    }

    private function redirectAfterPhoneVerification(User $user): RedirectResponse
    {
        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED], true)) {
            return redirect()->route('approval.pending');
        }

        return $this->postAuthRedirector->redirect($user);
    }
}
