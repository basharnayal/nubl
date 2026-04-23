<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Membership types. Extensible for future provider type.
     */
    public const MEMBERSHIP_DONOR = 'donor';

    /**
     * Admin membership type (separate from Spatie role).
     *
     * Note: authorization and dashboard routing are still role-based (`admin` role).
     * This value is mainly for consistent categorization in UI/admin tooling.
     */
    public const MEMBERSHIP_ADMIN = 'admin';

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
        'phone_verified_at',
        'rejection_reason',
        'is_active',
        'accepting_orders',
        'allocation_paused',
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
     * Get the menu items for this provider (when user is a provider).
     */
    public function providerMenuItems(): HasMany
    {
        return $this->hasMany(ProviderMenuItem::class, 'provider_id');
    }

    /**
     * Bank payout requests for this provider (weekly settlement queue).
     */
    public function providerPayouts(): HasMany
    {
        return $this->hasMany(ProviderPayout::class, 'provider_id');
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'accepting_orders' => 'boolean',
            'allocation_paused' => 'boolean',
        ];
    }

    /**
     * Provider is visible to recipients (shop open): account active, not admin-deactivated, accepting orders.
     */
    public function isOpenForRecipients(): bool
    {
        return $this->is_active
            && $this->accepting_orders
            && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\User>
     */
    public function scopeOpenForRecipients($query)
    {
        return $query->where('is_active', true)
            ->where('accepting_orders', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Find user by normalized phone (users.phone_number or provider_profile.phone_number).
     */
    public static function findByPhone(string $normalizedPhone): ?self
    {
        $user = static::where('phone_number', $normalizedPhone)->first();
        if ($user) {
            return $user;
        }

        return static::whereHas('providerProfile', fn ($q) => $q->where('phone_number', $normalizedPhone))->first();
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
        if (! $this->is_active) {
            return false;
        }

        return ! in_array($this->status, [self::STATUS_PENDING_APPROVAL, self::STATUS_REJECTED]);
    }

    /**
     * Check if phone verification is required (primary verification method).
     */
    public static function phoneVerificationRequired(): bool
    {
        return config('app.phone_verification_enabled', true);
    }

    /**
     * Check if user's phone is verified.
     */
    public function hasVerifiedPhone(): bool
    {
        if (! self::phoneVerificationRequired()) {
            return true;
        }

        return $this->phone_verified_at !== null;
    }

    /**
     * Check if email verification is required (based on config).
     */
    public static function emailVerificationRequired(): bool
    {
        return config('app.email_verification_enabled', true);
    }

    /**
     * Check if user's email is verified (or if verification is disabled).
     */
    public function isEmailVerified(): bool
    {
        // If email verification is disabled, consider all users as verified
        if (! self::emailVerificationRequired()) {
            return true;
        }

        return $this->hasVerifiedEmail();
    }
}
