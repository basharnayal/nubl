<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Queries\Admin\AdminFundTransactionIndexQuery;
use App\Models\FundTransaction;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFundTransactionController extends Controller
{
    public function __construct(
        private AdminFundTransactionIndexQuery $fundTransactionIndexQuery,
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $fundTransactions = ($this->fundTransactionIndexQuery)($request, 15);

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

    public function export(Request $request): StreamedResponse
    {
        $query = $this->fundTransactionIndexQuery->buildQuery($request);
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
        $query = $this->fundTransactionIndexQuery->buildQuery($request);
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
}
