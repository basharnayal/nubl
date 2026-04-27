<?php

namespace App\Auth\Registration\Registrars;

use App\Auth\Registration\Contracts\AccountRegistrar;
use App\Auth\Registration\Data\RegistrationData;
use App\Contracts\NotificationServiceInterface;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OtpService;
use App\Support\PhoneHelper;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Template method pattern for all account registration flows.
 *
 * Transaction boundary:
 *   - createBaseUser, assignRole, createTypeSpecificData
 *
 * After transaction:
 *   - event(Registered), Auth::login, OTP, admin notification
 *   - audit log (silent failure — never rolls back registration)
 */
abstract class AbstractAccountRegistrar implements AccountRegistrar
{
    public function __construct(
        protected NotificationServiceInterface $notifications,
        protected AuditService $audit,
        protected OtpService $otp,
    ) {}

    /**
     * Final template method. Subclasses extend via hooks only.
     */
    final public function register(RegistrationData $data): User
    {
        $artifacts = $this->prepareArtifacts($data);

        try {
            $user = DB::transaction(function () use ($data, $artifacts): User {
                $user = $this->createBaseUser($data);
                $user->assignRole($this->roleName());
                $this->createTypeSpecificData($user, $data, $artifacts);

                return $user;
            });
        } catch (\Throwable $e) {
            $this->cleanupArtifacts($artifacts);
            throw $e;
        }

        event(new Registered($user));
        Auth::login($user);

        if (config('app.phone_verification_enabled', true)) {
            $this->otp->sendOtp($user);
        }

        $this->notifications->sendNewUserRegisteredToAdmins($user);

        try {
            $this->audit->log('registration', 'completed', [
                'user_id'           => $user->id,
                'membership_type'   => $user->membership_type,
                'requires_approval' => $this->initialStatus() !== User::STATUS_ACTIVE,
            ], $user->id);
        } catch (\Throwable $e) {
            Log::error('Registration audit log failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return $user;
    }

    /**
     * Creates the base users row — shared across all account types.
     */
    final protected function createBaseUser(RegistrationData $data): User
    {
        return User::create([
            'name'            => $data->name,
            'email'           => $data->email,
            'password'        => Hash::make($data->password),
            'phone_number'    => PhoneHelper::normalize($data->phoneNumber),
            'membership_type' => $this->membershipType(),
            'status'          => $this->initialStatus(),
        ]);
    }

    // ── Mandatory hooks ─────────────────────────────────────────────────────

    abstract protected function membershipType(): string;

    abstract protected function initialStatus(): string;

    abstract protected function roleName(): string;

    // ── Optional hooks (default: no-op / empty) ──────────────────────────────

    /**
     * Upload files / decode base64 images before the transaction begins.
     * Return an array of paths/data that createTypeSpecificData and cleanupArtifacts will use.
     *
     * @return array<string, mixed>
     */
    protected function prepareArtifacts(RegistrationData $data): array
    {
        return [];
    }

    /**
     * Insert type-specific rows (profiles, KYC, documents…) inside the open transaction.
     *
     * @param  array<string, mixed>  $artifacts
     */
    protected function createTypeSpecificData(User $user, RegistrationData $data, array $artifacts): void
    {
        // no-op for types with no extra tables (e.g. Donor)
    }

    /**
     * Delete uploaded files when the transaction fails.
     * Override only in registrars that upload files in prepareArtifacts.
     *
     * @param  array<string, mixed>  $artifacts
     */
    protected function cleanupArtifacts(array $artifacts): void
    {
        // no-op by default
    }
}
