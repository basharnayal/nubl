<?php

namespace App\Http\Queries\Admin;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminPaymentIndexQuery
{
    public function buildQuery(Request $request): Builder
    {
        $query = Payment::query()->with(['sponsor']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('external_payment_id', 'like', "%{$search}%")
                    ->orWhereHas('sponsor', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->status;
            if ($status === 'PROBLEM_GROUP') {
                $query->whereIn('status', [
                    Payment::STATUS_FAILED,
                    Payment::STATUS_PENDING,
                    Payment::STATUS_PROCESSING,
                ]);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('donor_id')) {
            $query->where('sponsor_id', (int) $request->donor_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        return $query->orderByDesc('id');
    }

    public function __invoke(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildQuery($request)->paginate($perPage)->withQueryString();
    }
}
