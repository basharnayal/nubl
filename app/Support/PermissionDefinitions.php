<?php

namespace App\Support;

/**
 * Single source of truth for permission names used by seeders and the admin RBAC UI.
 *
 * Codebase audit: every `User::can('...')` / `abort_unless(...->can(...))` check lives under
 * `app/Http/Controllers/Admin/` and references only:
 * `qr.configure_ttl`, `allowances.configure`, `reports.export_csv` — all listed in {@see self::admin()}.
 * Routes use `role:*` middleware; `permission:*` is registered but unused on routes.
 * If you add a new can() check, add the permission name here and in lang en.json / ar.json as rbac.permission.{name}.
 */
class PermissionDefinitions
{
    /**
     * @return list<string>
     */
    public static function admin(): array
    {
        return [
            'accounts.approve',
            'requests.review',
            'requests.approve',
            'requests.reject',
            'qr.configure_ttl',
            'users.create',
            'users.read',
            'users.update',
            'users.delete',
            'users.manage',
            'users.assign.roles',
            'funds.create',
            'funds.read',
            'funds.update',
            'funds.delete',
            'policies.create',
            'policies.read',
            'policies.update',
            'policies.delete',
            'reports.export_csv',
            'reports.export_pdf',
            'allowances.configure',
            'users.deactivate',
            'users.reactivate',
            'allocation.pause_global',
            'allocation.pause_per_provider',
            'roles.manage',
            'permissions.manage',
        ];
    }

    /**
     * @return list<string>
     */
    public static function donor(): array
    {
        return [
            'donations.process',
            'dashboard.donor.view_stats',
        ];
    }

    /**
     * @return list<string>
     */
    public static function recipient(): array
    {
        return [
            'requests.submit',
        ];
    }

    /**
     * @return list<string>
     */
    public static function provider(): array
    {
        return [
            'qr.redeem',
            'fulfillment_proof.upload',
            'requests.adopt',
            'provider.capacity.toggle',
            'provider.pickup_notes_and_hours.update',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(
            self::admin(),
            self::donor(),
            self::recipient(),
            self::provider(),
        );
    }

    /**
     * Permission groups for the admin UI (labels use __() at render time).
     *
     * @return list<array{key: string, label_key: string, names: list<string>}>
     */
    public static function uiGroups(): array
    {
        return [
            [
                'key' => 'admin',
                'label_key' => 'rbac.group.admin_permissions',
                'names' => self::admin(),
            ],
            [
                'key' => 'donor',
                'label_key' => 'rbac.group.donor_permissions',
                'names' => self::donor(),
            ],
            [
                'key' => 'recipient',
                'label_key' => 'rbac.group.recipient_permissions',
                'names' => self::recipient(),
            ],
            [
                'key' => 'provider',
                'label_key' => 'rbac.group.provider_permissions',
                'names' => self::provider(),
            ],
        ];
    }
}
