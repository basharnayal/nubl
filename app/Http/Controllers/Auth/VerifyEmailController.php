<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterVerification($request);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            $this->auditService->log('auth', 'email_verified', [
                'user_id' => $request->user()->id,
            ], $request->user()->id);
        }

        return $this->redirectAfterVerification($request);
    }

    /**
     * Redirect user after email verification. Pending approval users go to approval page.
     */
    protected function redirectAfterVerification(EmailVerificationRequest $request): RedirectResponse
    {
        if (in_array($request->user()->status, [\App\Models\User::STATUS_PENDING_APPROVAL, \App\Models\User::STATUS_REJECTED])) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
