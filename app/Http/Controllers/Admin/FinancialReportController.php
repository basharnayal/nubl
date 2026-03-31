<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\Admin\AdminFinancialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(
        private AdminFinancialService $financialService
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,monthly,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $period = $validated['period'] ?? 'custom';

        if ($period === 'daily') {
            $from = Carbon::now()->startOfDay();
            $to = Carbon::now()->endOfDay();
        } elseif ($period === 'monthly') {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } else {
            $from = isset($validated['date_from'])
                ? Carbon::parse($validated['date_from'])->startOfDay()
                : Carbon::now()->subDays(30)->startOfDay();
            $to = isset($validated['date_to'])
                ? Carbon::parse($validated['date_to'])->endOfDay()
                : Carbon::now()->endOfDay();
        }

        $summary = $this->financialService->getRangeSummary($from, $to);

        return view('admin.finances.reports.index', [
            'summary' => $summary,
            'period' => $period,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
        ]);
    }
}
