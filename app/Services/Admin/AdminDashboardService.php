<?php

namespace App\Services\Admin;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\Admin\Dashboard\AttentionQueueBuilder;
use App\Services\Admin\Dashboard\SystemStatusChecker;
use Spatie\Activitylog\Models\Activity;

/**
 * Orchestrates all data required by the Admin Command Center dashboard.
 *
 * Delegates to:
 *  - SystemStatusChecker       → status strip pills
 *  - AttentionQueueBuilder     → action queue (sorted by severity)
 *  - AdminFinancialService     → financial snapshot (reused — no duplication)
 *
 * Privacy rule: this service MUST NOT return individual recipient names,
 * identity numbers, documents, or any PII. Aggregate counts only.
 */
class AdminDashboardService
{
    public function __construct(
        private readonly SystemStatusChecker   $statusChecker,
        private readonly AttentionQueueBuilder $attentionQueue,
        private readonly AdminFinancialService $financialService,
    ) {}

    public function getOverview(): array
    {
        return [
            'system_status'   => $this->statusChecker->all(),
            'attention_items' => $this->attentionQueue->build(),
            'kpis'            => $this->buildKpis(),
            'financial'       => $this->financialService->getOverview(),
            'platform'        => $this->buildPlatformSnapshot(),
            'recent_activity' => $this->buildRecentActivity(),
        ];
    }

    // ── KPI cards ─────────────────────────────────────────────────────────────

    private function buildKpis(): array
    {
        $pendingTotal      = User::where('status', User::STATUS_PENDING_APPROVAL)->count();
        $pendingRecipients = User::where('status', User::STATUS_PENDING_APPROVAL)
            ->where('membership_type', User::MEMBERSHIP_RECIPIENT)->count();
        $pendingProviders  = User::where('status', User::STATUS_PENDING_APPROVAL)
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)->count();

        $openRequests       = RequestModel::whereIn('status', ['REQUESTED', 'APPROVED', 'REDEEMABLE'])->count();
        $awaitingAssignment = RequestModel::where('status', 'REQUESTED')->count();
        $inProgress         = RequestModel::whereIn('status', ['APPROVED', 'REDEEMABLE'])->count();

        $financial = $this->financialService->getOverview();

        $approvedProviders = User::where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)->count();
        $totalProviders    = User::where('membership_type', User::MEMBERSHIP_PROVIDER)->count();

        return [
            [
                'key'          => 'pending_approvals',
                'label_key'    => 'dashboard.kpi.pending_approvals.label',
                'value'        => $pendingTotal,
                'value_format' => 'integer',
                'sub_key'      => 'dashboard.kpi.pending_approvals.sub',
                'sub_params'   => ['recipients' => $pendingRecipients, 'providers' => $pendingProviders],
                'icon'         => 'fa-solid fa-user-clock',
                'color'        => 'amber',
                'route'        => 'admin.users.pending',
                'action_key'   => 'dashboard.kpi.pending_approvals.action',
            ],
            [
                'key'          => 'open_requests',
                'label_key'    => 'dashboard.kpi.open_requests.label',
                'value'        => $openRequests,
                'value_format' => 'integer',
                'sub_key'      => 'dashboard.kpi.open_requests.sub',
                'sub_params'   => ['awaiting' => $awaitingAssignment, 'in_progress' => $inProgress],
                'icon'         => 'fa-solid fa-inbox',
                'color'        => 'blue',
                'route'        => 'admin.requests.index',
                'action_key'   => 'dashboard.kpi.open_requests.action',
            ],
            [
                'key'          => 'wallet_balance',
                'label_key'    => 'dashboard.kpi.wallet_balance.label',
                'value'        => $financial['system_wallet_balance'],
                'value_format' => 'currency',
                'sub_key'      => 'dashboard.kpi.wallet_balance.sub',
                'sub_params'   => [],
                'icon'         => 'fa-solid fa-wallet',
                'color'        => 'green',
                'route'        => 'admin.finances.overview',
                'action_key'   => 'dashboard.kpi.wallet_balance.action',
            ],
            [
                'key'          => 'approved_providers',
                'label_key'    => 'dashboard.kpi.approved_providers.label',
                'value'        => $approvedProviders,
                'value_format' => 'integer',
                'sub_key'      => 'dashboard.kpi.approved_providers.sub',
                'sub_params'   => ['total' => $totalProviders],
                'icon'         => 'fa-solid fa-store',
                'color'        => 'purple',
                'route'        => 'admin.manage.users.index',
                'action_key'   => 'dashboard.kpi.approved_providers.action',
            ],
        ];
    }

    // ── Platform snapshot (privacy-safe aggregates only) ──────────────────────

    private function buildPlatformSnapshot(): array
    {
        return [
            'total_users'        => User::count(),
            'donors'             => User::where('membership_type', User::MEMBERSHIP_DONOR)->count(),
            'recipients'         => User::where('membership_type', User::MEMBERSHIP_RECIPIENT)->count(),
            'providers'          => User::where('membership_type', User::MEMBERSHIP_PROVIDER)->count(),
            'approved_providers' => User::where('membership_type', User::MEMBERSHIP_PROVIDER)
                ->where('status', User::STATUS_ACTIVE)->count(),
            'pending_users'      => User::where('status', User::STATUS_PENDING_APPROVAL)->count(),
            'requests_30d'       => RequestModel::where('created_at', '>=', now()->subDays(30))->count(),
            'fulfilled_30d'      => RequestModel::where('status', 'FULFILLED')
                ->where('updated_at', '>=', now()->subDays(30))->count(),
        ];
    }

    // ── Recent audit activity ─────────────────────────────────────────────────

    private function buildRecentActivity(): array
    {
        return Activity::query()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $a) => [
                'id'          => $a->id,
                'description' => $a->description,
                'log_name'    => $a->log_name,
                'causer_name' => $a->causer?->name ?? 'System',
                'created_at'  => $a->created_at,
            ])
            ->toArray();
    }
}
