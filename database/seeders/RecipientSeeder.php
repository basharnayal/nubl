<?php

namespace Database\Seeders;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RecipientSeeder extends Seeder
{
    /**
     * Seed recipient users with profile and KYC details.
     */
    public function run(): void
    {
        $recipientRole = Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $recipient = User::firstOrCreate(
            ['email' => 'recipient@nubl.com'],
            [
                'name' => 'Recipient User',
                'email' => 'recipient@nubl.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'membership_type' => User::MEMBERSHIP_RECIPIENT,
                'status' => User::STATUS_ACTIVE,
                'phone_number' => '966501234567',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $recipient->assignRole($recipientRole);

        RecipientProfile::firstOrCreate(
            ['user_id' => $recipient->id],
            [
                'nationality' => 'Saudi Arabia',
                'short_address' => 'Riyadh, Al Malaz District',
                'id_type' => 'national_id',
                'id_photo_path' => 'recipient_id_photos/seed-placeholder',
            ]
        );

        RecipientKycDetails::firstOrCreate(
            ['user_id' => $recipient->id],
            [
                'income_band' => '1000-1500',
                'household_size' => 4,
                'marital_status' => 'married',
                'is_student' => false,
                'address_confirmation' => null,
            ]
        );
    }
}
