<?php

namespace App\Services;

use App\Models\User;

/**
 * Wrapper around Spatie Activity Log for consistent audit logging.
 * Use this service to log important events (user actions, approvals, etc.).
 */
class AuditService
{
    /**
     * Log an audit event.
     *
     * @param  string  $entity  Entity type (e.g. 'user', 'donation', 'menu_item', 'account_approval')
     * @param  string  $action  Action performed (e.g. 'created', 'updated', 'deactivated', 'approved')
     * @param  array<string, mixed>  $data  Additional context (IDs, amounts, etc.)
     * @param  int|null  $userId  User who performed the action (default: current user)
     */
    public function log(string $entity, string $action, array $data = [], ?int $userId = null): void
    {
        $causer = $userId ? User::find($userId) : auth()->user();

        activity()
            ->causedBy($causer)
            ->withProperties(array_merge(
                ['entity' => $entity, 'action' => $action],
                $data
            ))
            ->log("{$entity}.{$action}");
    }
}
