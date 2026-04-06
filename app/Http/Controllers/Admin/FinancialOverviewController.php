<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\Admin\AdminFinancialService;
use App\Support\FinancialMath;
use Illuminate\View\View;

class FinancialOverviewController extends Controller
{
    public function __construct(
        private AdminFinancialService $financialService
    ) {}

    public function index(): View
    {
        $overview = $this->financialService->getOverview();
        $overview['unsuccessful_payments_amount'] = (float) FinancialMath::add(
            FinancialMath::normalize((string) $overview['pending_amount']),
            FinancialMath::normalize((string) $overview['failed_amount'])
        );
        $overview['unsuccessful_payments_count'] = (int) $overview['pending_count'] + (int) $overview['failed_count'];

        $chartPayments = $this->buildPaymentsDonutConfig($overview);
        $chartLedger = $this->buildLedgerBarConfig($overview);

        return view('admin.finances.overview', compact('overview', 'chartPayments', 'chartLedger'));
    }

    /**
     * @param  array<string, float|int>  $overview
     * @return array{config: array<string, mixed>, has_data: bool}
     */
    private function buildPaymentsDonutConfig(array $overview): array
    {
        $round = fn (float $v) => round($v, 2);
        $sOk = $round((float) $overview['successful_payments_amount']);
        $sUnsuccessful = $round((float) $overview['unsuccessful_payments_amount']);
        $sum = $sOk + $sUnsuccessful;

        $rtl = app()->getLocale() === 'ar';

        $config = [
            'series' => [$sOk, $sUnsuccessful],
            'labels' => [
                __('finance.overview.chart_legend_successful'),
                __('finance.overview.chart_legend_unsuccessful'),
            ],
            'chart' => [
                'type' => 'donut',
                'height' => 300,
                'fontFamily' => 'inherit',
                'toolbar' => ['show' => false],
                'rtl' => $rtl,
            ],
            'colors' => ['#15803d', '#b91c1c'],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '12px',
                'markers' => ['width' => 10, 'height' => 10, 'radius' => 2],
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '72%',
                    ],
                ],
            ],
            'stroke' => [
                'width' => 2,
                'colors' => ['#ffffff'],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];

        return [
            'config' => $config,
            'has_data' => $sum > 0,
        ];
    }

    /**
     * @param  array<string, float|int>  $overview
     * @return array{config: array<string, mixed>, has_data: bool}
     */
    private function buildLedgerBarConfig(array $overview): array
    {
        $round = fn (float $v) => round($v, 2);
        $in = $round((float) $overview['fund_inbound_system']);
        $out = $round((float) $overview['fund_outbound_system']);
        $payout = $round((float) $overview['transfers_to_providers']);
        $sum = $in + $out + $payout;

        $rtl = app()->getLocale() === 'ar';

        $config = [
            'series' => [
                [
                    'name' => __('finance.common.sar'),
                    'data' => [$in, $out, $payout],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'height' => 280,
                'fontFamily' => 'inherit',
                'toolbar' => ['show' => false],
                'rtl' => $rtl,
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'barHeight' => '62%',
                    'distributed' => true,
                ],
            ],
            'colors' => ['#4f46e5', '#475569', '#9333ea'],
            'xaxis' => [
                'categories' => [
                    __('finance.overview.bar_category_in'),
                    __('finance.overview.bar_category_out'),
                    __('finance.overview.bar_category_payout'),
                ],
                'labels' => ['style' => ['fontSize' => '11px', 'colors' => '#64748b']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontSize' => '11px', 'colors' => '#64748b']],
            ],
            'dataLabels' => [
                'enabled' => true,
                'textAnchor' => 'middle',
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 700,
                    'colors' => ['#ffffff', '#ffffff', '#ffffff'],
                ],
                'dropShadow' => [
                    'enabled' => true,
                    'top' => 1,
                    'left' => 1,
                    'blur' => 2,
                    'opacity' => 0.45,
                    'color' => '#000000',
                ],
            ],
            'legend' => ['show' => false],
            'grid' => [
                'borderColor' => 'rgba(148, 163, 184, 0.15)',
                'xaxis' => ['lines' => ['show' => true]],
                'yaxis' => ['lines' => ['show' => false]],
            ],
        ];

        return [
            'config' => $config,
            'has_data' => $sum > 0,
        ];
    }
}
