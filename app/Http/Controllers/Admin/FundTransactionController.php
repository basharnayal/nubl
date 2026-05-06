<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundTransaction;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FundTransactionController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $fundTransactions = $this->buildIndexQuery($request)->paginate(15)->withQueryString();

        return view('admin.finances.fund-transactions.index', compact('fundTransactions'));
    }

    public function show(FundTransaction $fundTransaction): View
    {
        $fundTransaction->load([
            'wallet.provider.user',
            'sponsor',
            'payment.sponsor',
            'request.recipient',
            'request.provider',
            'orderRedemption',
        ]);

        $auditEntries = $this->resolveAuditEntries($fundTransaction);

        return view('admin.finances.fund-transactions.show', compact('fundTransaction', 'auditEntries'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildIndexQuery($request);
        $query->with(['wallet', 'sponsor', 'payment'])->reorder()->orderBy('id');

        $filename = 'fund-transactions-'.now()->format('Y-m-d-His').'.csv';

        $this->auditService->log('finance', 'fund_transactions_exported', [
            'decision' => 'export',
            'export_type' => 'fund_transactions_csv',
            'filters' => $request->query(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'wallet_id',
                'wallet_owner_type',
                'direction',
                'source',
                'amount',
                'payment_id',
                'request_id',
                'order_redemption_id',
                'donor_id',
                'donor_name',
                'created_at',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $tx) {
                    $donorName = $tx->sponsor?->name ?? ($tx->payment?->is_guest ? __('Guest Donor') : null);
                    fputcsv($out, [
                        $tx->id,
                        $tx->wallet_id,
                        $tx->wallet?->owner_type,
                        $tx->direction,
                        $tx->source,
                        $tx->amount,
                        $tx->payment_id,
                        $tx->request_id,
                        $tx->order_redemption_id,
                        $tx->sponsor_id,
                        $donorName,
                        $tx->created_at?->toIso8601String(),
                    ]);
                }
            });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $query = $this->buildIndexQuery($request);
        $query->with(['wallet', 'sponsor', 'payment'])->reorder()->orderBy('id');

        $transactions = $query->get();
        $filename = 'fund-transactions-'.now()->format('Y-m-d-His').'.pdf';

        $this->auditService->log('finance', 'fund_transactions_exported', [
            'decision' => 'export',
            'export_type' => 'fund_transactions_pdf',
            'filters' => $request->query(),
        ]);

        return Pdf::loadView('admin.finances.exports.fund-transactions-pdf', [
            'transactions' => $transactions,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    private function buildIndexQuery(Request $request): Builder
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

    /**
     * @return Collection<int, Activity>
     */
    private function resolveAuditEntries(FundTransaction $fundTransaction): Collection
    {
        if (! $fundTransaction->payment_id && ! $fundTransaction->request_id) {
            return collect();
        }

        return Activity::query()
            ->where(function ($q) use ($fundTransaction) {
                if ($fundTransaction->payment_id && $fundTransaction->request_id) {
                    $q->where('properties->payment_id', $fundTransaction->payment_id)
                        ->orWhere('properties->request_id', $fundTransaction->request_id);
                } elseif ($fundTransaction->payment_id) {
                    $q->where('properties->payment_id', $fundTransaction->payment_id);
                } else {
                    $q->where('properties->request_id', $fundTransaction->request_id);
                }
            })
            ->latest()
            ->limit(100)
            ->get();
    }
}
