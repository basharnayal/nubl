<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; 

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
<<<<<<< Updated upstream
=======
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
        return $this->hasOne(ProviderProfile::class, 'user_id');
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
    public function menuItems(): HasMany
    {
        return $this->hasMany(ProviderMenuItem::class, 'provider_id');
    }

    /**
     * Check if the user has full access (not pending approval).
     */
    public function hasFullAccess(): bool
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
>>>>>>> Stashed changes
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
        ];
    }
}
