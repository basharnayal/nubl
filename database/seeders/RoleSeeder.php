<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
        $this->command->info('✅ Assigned ' . count($adminPermissions) . ' permissions to admin role (all permissions)');

        // Donor Permissions
        $donorPermissions = [
            'donations.process',              // FR 3.1
            'dashboard.donor.view_stats',     // FR 4.1
        ];
        $donorRole->syncPermissions($donorPermissions);
        $this->command->info('✅ Assigned ' . count($donorPermissions) . ' permissions to donor role');

        // Recipient Permissions
        $recipientPermissions = [
            'requests.submit',                // FR 5.1
        ];
        $recipientRole->syncPermissions($recipientPermissions);
        $this->command->info('✅ Assigned ' . count($recipientPermissions) . ' permissions to recipient role');

        // Provider Permissions
        $providerPermissions = [
            'qr.redeem',                                      // FR 9.1
            'fulfillment_proof.upload',                      // FR 10.1
            'requests.adopt',                                 // FR 21.1
            'provider.capacity.toggle',                       // FR 23.1
            'provider.pickup_notes_and_hours.update',        // FR 23.2
        ];
        $providerRole->syncPermissions($providerPermissions);
        $this->command->info('✅ Assigned ' . count($providerPermissions) . ' permissions to provider role');
    }
}
