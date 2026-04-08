<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Queries\Admin\AdminPaymentIndexQuery;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPaymentController extends Controller
{
    public function __construct(
        private AdminPaymentIndexQuery $paymentIndexQuery,
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $payments = ($this->paymentIndexQuery)($request, 15);

        return view('admin.finances.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'sponsor',
            'fundTransactions.wallet',
            'requestPaymentLinks.request',
        ]);

        $auditEntries = Activity::query()
            ->where('properties->payment_id', $payment->id)
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.finances.payments.show', compact('payment', 'auditEntries'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->paymentIndexQuery->buildQuery($request);
        $query->with('sponsor')->reorder()->orderBy('id');

        $filename = 'payments-'.now()->format('Y-m-d-His').'.csv';

        $this->auditService->log('finance', 'payments_exported', [
            'decision' => 'export',
            'export_type' => 'payments_csv',
            'filters' => $request->query(),
        ]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'sponsor_id',
                'donor_name',
                'donor_email',
                'gateway',
                'external_payment_id',
                'status',
                'amount',
                'created_at',
                'updated_at',
            ]);

            $query->orderBy('id')->chunk(500, function ($payments) use ($out) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->sponsor_id,
                        $p->sponsor?->name,
                        $p->sponsor?->email,
                        $p->gateway,
                        $p->external_payment_id,
                        $p->status,
                        $p->amount,
                        $p->created_at?->toIso8601String(),
                        $p->updated_at?->toIso8601String(),
                    ]);
                }
            });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
