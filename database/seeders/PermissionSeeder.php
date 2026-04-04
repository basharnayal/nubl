<?php

namespace Database\Seeders;

use App\Permissions\PermissionDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = PermissionDefinitions::all();

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('✅ Created '.count($allPermissions).' permissions');
        $this->command->info('   - Admin: '.count(PermissionDefinitions::admin()));
        $this->command->info('   - Donor: '.count(PermissionDefinitions::donor()));
        $this->command->info('   - Recipient: '.count(PermissionDefinitions::recipient()));
        $this->command->info('   - Provider: '.count(PermissionDefinitions::provider()));
    }
}
