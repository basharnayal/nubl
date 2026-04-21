<?php

namespace Database\Seeders;

use App\Support\PermissionDefinitions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================
        // CREATE ROLES
        // ============================================
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $donorRole = Role::firstOrCreate(['name' => 'donor']);
        $recipientRole = Role::firstOrCreate(['name' => 'recipient']);
        $providerRole = Role::firstOrCreate(['name' => 'provider']);

        $this->command->info('✅ Created 4 roles');

        // ============================================
        // ASSIGN PERMISSIONS TO ROLES
        // ============================================

        // Admin Permissions (All permissions)
        $adminPermissions = Permission::all()->pluck('name')->toArray();
        $adminRole->syncPermissions($adminPermissions);
        $this->command->info('✅ Assigned '.count($adminPermissions).' permissions to admin role (all permissions)');

        // Donor / recipient / provider — keep in sync with PermissionDefinitions
        $donorPermissions = PermissionDefinitions::donor();
        $donorRole->syncPermissions($donorPermissions);
        $this->command->info('✅ Assigned '.count($donorPermissions).' permissions to donor role');

        $recipientPermissions = PermissionDefinitions::recipient();
        $recipientRole->syncPermissions($recipientPermissions);
        $this->command->info('✅ Assigned '.count($recipientPermissions).' permissions to recipient role');

        $providerPermissions = PermissionDefinitions::provider();
        $providerRole->syncPermissions($providerPermissions);
        $this->command->info('✅ Assigned '.count($providerPermissions).' permissions to provider role');
    }
}
