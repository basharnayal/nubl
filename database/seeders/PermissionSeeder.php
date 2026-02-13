<?php

namespace Database\Seeders;

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

        // ============================================
        // ADMIN PERMISSIONS
        // ============================================
        $adminPermissions = [
            // FR 1.4 - Approve accounts (Recipient/Provider)
            'accounts.approve',

            // FR 7.1 - Review/approve/reject requests
            'requests.review',
            'requests.approve',
            'requests.reject',

            // FR 8.3 - Configure QR TTL
            'qr.configure_ttl',

            // FR 12.1 - Full CRUD for Users
            'users.create',
            'users.read',
            'users.update',
            'users.delete',
            'users.manage',              // General user management (for admin panel)
            'users.assign.roles',       // Assign roles to users (for admin panel)

            // FR 12.1 - Full CRUD for Funds
            'funds.create',
            'funds.read',
            'funds.update',
            'funds.delete',

            // FR 12.1 - Full CRUD for Policies
            'policies.create',
            'policies.read',
            'policies.update',
            'policies.delete',

            // FR 15.1 - Export audit & financial reports
            'reports.export_csv',
            'reports.export_pdf',

            // FR 17.1 - Configure system-wide allowance values
            'allowances.configure',

            // FR 20.1 - Deactivate/Reactivate accounts
            'users.deactivate',
            'users.reactivate',

            // FR 24.1 - Pause allocation engine
            'allocation.pause_global',
            'allocation.pause_per_provider',

            // Admin Panel Management (Required for admin interfaces)
            'roles.manage',              // Manage roles (for admin panel)
            'permissions.manage',       // Manage permissions (for admin panel)
        ];

        // ============================================
        // DONOR PERMISSIONS
        // ============================================
        $donorPermissions = [
            // FR 3.1 - Process donations via MyFatoorah
            'donations.process',

            // FR 4.1 - View aggregated donor dashboard statistics
            'dashboard.donor.view_stats',
        ];

        // ============================================
        // RECIPIENT PERMISSIONS
        // ============================================
        $recipientPermissions = [
            // FR 5.1 - Submit digital item requests
            'requests.submit',
        ];

        // ============================================
        // PROVIDER PERMISSIONS
        // ============================================
        $providerPermissions = [
            // FR 9.1 - Redeem QR codes (scan/manual)
            'qr.redeem',

            // FR 10.1 - Upload proof of fulfillment
            'fulfillment_proof.upload',

            // FR 21.1 - Adopt pending request and fulfill as donation
            'requests.adopt',

            // FR 23.1 - Toggle service capacity (ON/OFF)
            'provider.capacity.toggle',

            // FR 23.2 - Set pickup notes and operating hours
            'provider.pickup_notes_and_hours.update',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $adminPermissions,
            $donorPermissions,
            $recipientPermissions,
            $providerPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('✅ Created ' . count($allPermissions) . ' permissions');
        $this->command->info('   - Admin: ' . count($adminPermissions));
        $this->command->info('   - Donor: ' . count($donorPermissions));
        $this->command->info('   - Recipient: ' . count($recipientPermissions));
        $this->command->info('   - Provider: ' . count($providerPermissions));
    }
}
