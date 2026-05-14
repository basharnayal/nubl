<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Remove orphaned fund permissions that never mapped to real features.
 *
 * funds.create and funds.delete had no controller, route, or model backing them.
 * funds.read and funds.update are intentionally kept for future use.
 */
return new class extends Migration
{
    private const REMOVE = ['funds.create', 'funds.delete'];

    public function up(): void
    {
        // Delete pivot rows first, then the permission rows themselves.
        $ids = DB::table('permissions')
            ->whereIn('name', self::REMOVE)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Re-create the permissions so a rollback is non-destructive.
        $now = now();
        foreach (self::REMOVE as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
