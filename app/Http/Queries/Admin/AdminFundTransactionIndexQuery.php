<?php

namespace App\Http\Queries\Admin;

use App\Models\FundTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminFundTransactionIndexQuery
{
    public function buildQuery(Request $request): Builder
    {
        $query = FundTransaction::query()->with([
            'wallet.provider.user',
            'sponsor',
            'payment',
            'request',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            if (ctype_digit($search)) {
                $sid = (int) $search;
                $query->where(function ($q) use ($sid) {
                    $q->where('id', $sid)
                        ->orWhere('payment_id', $sid)
                        ->orWhere('request_id', $sid);
                });
            }
        }

        if ($request->filled('wallet_type')) {
            $query->whereHas('wallet', function ($wq) use ($request) {
                $wq->where('owner_type', $request->wallet_type);
            });
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('donor_id')) {
            $query->where('sponsor_id', (int) $request->donor_id);
        }

        if ($request->filled('provider_user_id')) {
            $providerUserId = (int) $request->provider_user_id;
            $query->whereHas('wallet', function ($wq) use ($providerUserId) {
                $wq->where('owner_type', 'PROVIDER')
                    ->whereHas('provider', function ($pq) use ($providerUserId) {
                        $pq->where('user_id', $providerUserId);
                    });
            });
        }

        if ($request->filled('request_id')) {
            $query->where('request_id', (int) $request->request_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query->orderByDesc('id');
    }

    public function __invoke(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildQuery($request)->paginate($perPage)->withQueryString();
    }
}
