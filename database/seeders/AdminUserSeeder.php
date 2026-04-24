<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default admin user (Spatie role: admin).
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@nubl.com'],
            [
                'name' => 'NUBL Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'membership_type' => User::MEMBERSHIP_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'phone_number' => '966501111111',
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $admin->assignRole($adminRole);
    }
}
