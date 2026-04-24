<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\EwalletSeeder;
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

        $this->call(AdminUserSeeder::class);

        // Step 2.5: Create donor user
        $this->call(DonorSeeder::class);

        // Step 3: Create system-level ewallet
        $this->call(EwalletSeeder::class);

        // Step 4: Create recipient user
        $this->call(RecipientSeeder::class);

        // Step 5: Create providers and menu items (menu categories before menu items)
        $this->call(ProviderSeeder::class);
        $this->call(MenuItemCategorySeeder::class);
        $this->call(ProviderMenuItemSeeder::class);

        // Step 6: Create allowance test data (REDEEMABLE + FULFILLED requests)
        $this->call(AllowanceTestDataSeeder::class);
    }
}
