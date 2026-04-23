<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DonorSeeder extends Seeder
{
    /**
     * Seed donor users (Spatie role: donor).
     */
    public function run(): void
    {
        $donorRole = Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $donors = [
            [
                'email' => 'donor@nubl.com',
                'name' => 'Donor User',
                'phone_number' => '966504567890',
            ],
            [
                'email' => 'donor-seed@nubl.com',
                'name' => 'Donor Seed',
                'phone_number' => '966504567891',
            ],
        ];

        foreach ($donors as $data) {
            $donor = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'membership_type' => User::MEMBERSHIP_DONOR,
                    'status' => User::STATUS_ACTIVE,
                    'phone_number' => $data['phone_number'],
                    'phone_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $donor->assignRole($donorRole);
        }
    }
}

