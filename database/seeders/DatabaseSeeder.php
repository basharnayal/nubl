<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Step 1: Create Permissions first
        $this->call(PermissionSeeder::class);

        // Step 2: Create Roles and assign Permissions
        $this->call(RoleSeeder::class);

        // Step 3: Create test users (optional)
        // Uncomment if you want to create test users
        
        // $admin = User::factory()->create([
        //     'name' => 'Admin User',
        //     'email' => 'admin@nubl.com',
        // ]);
        // $admin->assignRole('admin');

        // $donor = User::factory()->create([
        //     'name' => 'Donor User',
        //     'email' => 'donor@nubl.com',
        // ]);
        // $donor->assignRole('donor');

        // $recipient = User::factory()->create([
        //     'name' => 'Recipient User',
        //     'email' => 'recipient@nubl.com',
        // ]);
        // $recipient->assignRole('recipient');

        // $provider = User::factory()->create([
        //     'name' => 'Provider User',
        //     'email' => 'provider@nubl.com',
        // ]);
        // $provider->assignRole('provider');
    }
}
