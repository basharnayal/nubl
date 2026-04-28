<?php

namespace App\Console\Commands;

use App\Models\SummaryReport;
use App\Services\Admin\AdminFinancialService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * FR-19.1: Auto-generate weekly or monthly financial summary report and persist it
 * so admins can view and download it from the admin panel.
 *
 * Usage:
 *   php artisan report:generate --type=weekly
 *   php artisan report:generate --type=monthly
 */
class GenerateSummaryReportCommand extends Command
{
    protected $signature = 'report:generate
                            {--type=weekly : Report type — "weekly" or "monthly"}';

    protected $description = 'FR-19.1: Generate and store a weekly or monthly financial summary report.';

    public function handle(AdminFinancialService $financialService): int
    {
        $type = $this->option('type');

        if (! in_array($type, [SummaryReport::TYPE_WEEKLY, SummaryReport::TYPE_MONTHLY], true)) {
            $this->error("Invalid type '{$type}'. Use --type=weekly or --type=monthly.");

            return self::FAILURE;
        }

        [$from, $to] = $this->resolveDateRange($type);

        $this->info("Generating {$type} report: {$from->toDateString()} → {$to->toDateString()}");

        $payload = $financialService->getRangeSummary($from, $to);

        // Carbon instances are not JSON-serialisable directly; convert to strings
        $payload['from'] = $from->toDateString();
        $payload['to'] = $to->toDateString();

        // Serialise payment Eloquent collection to plain array
        if (isset($payload['payments_by_status'])) {
            $payload['payments_by_status'] = collect($payload['payments_by_status'])
                ->map(fn ($row) => ['status' => $row->status, 'cnt' => (int) $row->cnt, 'total' => (float) $row->total])
                ->values()
                ->toArray();
        }

        SummaryReport::create([
            'type' => $type,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'payload' => $payload,
            'generated_at' => now(),
        ]);

        $this->info("✅ {$type} report stored successfully.");

        return self::SUCCESS;
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function resolveDateRange(string $type): array
    {
        if ($type === SummaryReport::TYPE_MONTHLY) {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } else {
            // Weekly: previous full week (Mon–Sun)
            $from = Carbon::now()->subWeek()->startOfWeek();
            $to = Carbon::now()->subWeek()->endOfWeek();
        }

        return [$from, $to];
    }
}
