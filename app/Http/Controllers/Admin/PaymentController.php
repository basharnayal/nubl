<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $payments = $this->buildIndexQuery($request)->paginate(15)->withQueryString();

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
        $query = $this->buildIndexQuery($request);
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
                'donor_id',
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
                    $donorName = $p->sponsor?->name ?? ($p->is_guest ? __('Guest Donor') : null);

                    fputcsv($out, [
                        $p->id,
                        $p->sponsor_id,
                        $donorName,
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

    public function exportPdf(Request $request): Response
    {
        $query = $this->buildIndexQuery($request);
        $query->with('sponsor')->reorder()->orderBy('id');

        $payments = $query->get();
        $filename = 'payments-'.now()->format('Y-m-d-His').'.pdf';

        $this->auditService->log('finance', 'payments_exported', [
            'decision' => 'export',
            'export_type' => 'payments_pdf',
            'filters' => $request->query(),
        ]);

        return Pdf::loadView('admin.finances.exports.payments-pdf', [
            'payments' => $payments,
            'generated_at' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    private function buildIndexQuery(Request $request): Builder
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
}
