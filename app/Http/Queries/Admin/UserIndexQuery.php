<?php

namespace App\Http\Queries\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class UserIndexQuery
{
    private const SORTABLE = ['name', 'email', 'status', 'created_at'];

    public function __invoke(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['roles', 'providerProfile', 'recipientProfile']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $sort      = in_array($request->sort, self::SORTABLE) ? $request->sort : null;
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        if ($sort) {
            $query->orderBy($sort, $direction);
        } else {
            // Priority: pending_approval → active → login-disabled → rejected
            $query->orderByRaw("
                CASE
                    WHEN status = 'pending_approval'              THEN 1
                    WHEN status = 'active'  AND is_active = 1    THEN 2
                    WHEN is_active = 0      AND status != 'rejected' THEN 3
                    WHEN status = 'rejected'                      THEN 4
                    ELSE 5
                END
            ")
            ->orderByRaw(
                '(SELECT COUNT(*) FROM model_has_roles mhr
                  INNER JOIN roles r ON r.id = mhr.role_id
                  WHERE mhr.model_id = users.id AND mhr.model_type = ? AND r.name = ?) DESC',
                [User::class, 'admin']
            )
            ->orderBy('created_at', 'desc');
        }

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }
}
