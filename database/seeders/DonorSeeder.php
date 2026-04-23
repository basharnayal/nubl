<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DonorSeeder extends Seeder
{
    /**
     * Seed the default donor user (Spatie role: donor).
     */
    public function run(): void
    {
        $donorRole = Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $donor = User::firstOrCreate(
            ['email' => 'donor@nubl.com'],
            [
                'name' => 'Donor User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'membership_type' => User::MEMBERSHIP_DONOR,
                'status' => User::STATUS_ACTIVE,
                'phone_number' => '966504567890',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $donor->assignRole($donorRole);
    }
}

