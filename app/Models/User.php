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
    use HasFactory, Notifiable, HasRoles;

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
