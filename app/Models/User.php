<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Membership types. Extensible for future provider type.
     */
    public const MEMBERSHIP_DONOR = 'donor';
    public const MEMBERSHIP_RECIPIENT = 'recipient';
    public const MEMBERSHIP_PROVIDER = 'provider';

    /** active = full access. pending_approval = recipient awaiting admin approval */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_REJECTED = 'rejected';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'membership_type',
        'status',
        'phone_number',
        'rejection_reason',
        'is_active',
    ];

    /**
     * Get the recipient profile (for recipient users only).
     */
    public function recipientProfile(): HasOne
    {
        return $this->hasOne(RecipientProfile::class);
    }

    /**
     * Get the recipient KYC details (for recipient users only).
     */
    public function recipientKycDetails(): HasOne
    {
        return $this->hasOne(RecipientKycDetails::class);
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function providerOperatingInfo(): HasOne
    {
        return $this->hasOne(ProviderOperatingInfo::class);
    }

    public function providerFinancialInfo(): HasOne
    {
        return $this->hasOne(ProviderFinancialInfo::class);
    }

    public function providerDocuments(): HasOne
    {
        return $this->hasOne(ProviderDocuments::class);
    }

    /**
     * Check if the user has full access (not pending approval).
     */
    public function hasFullAccess(): bool
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope: only active users (not deactivated by admin).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only inactive (deactivated) users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Deactivate user (admin action). Blocks login.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactivate user (admin action).
     */
    public function reactivate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Check if user can log in (active + not pending/rejected).
     */
    public function canLogin(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        return !in_array($this->status, [self::STATUS_PENDING_APPROVAL, self::STATUS_REJECTED]);
    }

    /**
     * Check if email verification is required (based on config).
     *
     * @return bool
     */
    public static function emailVerificationRequired(): bool
    {
        return config('app.email_verification_enabled', true);
    }

    /**
     * Check if user's email is verified (or if verification is disabled).
     *
     * @return bool
     */
    public function isEmailVerified(): bool
    {
        // If email verification is disabled, consider all users as verified
        if (!self::emailVerificationRequired()) {
            return true;
        }

        return $this->hasVerifiedEmail();
    }
}
