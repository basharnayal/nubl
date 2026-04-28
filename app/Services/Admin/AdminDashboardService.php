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
        private readonly SystemStatusChecker $statusChecker,
        private readonly AttentionQueueBuilder $attentionQueue,
        private readonly AdminFinancialService $financialService,
    ) {}

    public function getOverview(): array
    {
        // Fetched once here and passed down — never called twice.
        $financial = $this->financialService->getOverview();

        return [
            'system_status' => $this->statusChecker->all(),
            'attention_items' => $this->attentionQueue->build(),
            'kpis' => $this->buildKpis($financial),
            'financial' => $financial,
            'platform' => $this->buildPlatformSnapshot(),
            'recent_activity' => $this->buildRecentActivity(),
        ];
    }

    // ── KPI cards ─────────────────────────────────────────────────────────────

    private function buildKpis(array $financial): array
    {
        // 5 individual User counts → 1 aggregate query
        $userStats = User::query()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)                               AS pending_total,
                 SUM(CASE WHEN status = ? AND membership_type = ? THEN 1 ELSE 0 END)       AS pending_recipients,
                 SUM(CASE WHEN status = ? AND membership_type = ? THEN 1 ELSE 0 END)       AS pending_providers,
                 SUM(CASE WHEN membership_type = ? AND status = ? THEN 1 ELSE 0 END)       AS approved_providers,
                 SUM(CASE WHEN membership_type = ? THEN 1 ELSE 0 END)                      AS total_providers',
                [
                    User::STATUS_PENDING_APPROVAL,
                    User::STATUS_PENDING_APPROVAL, User::MEMBERSHIP_RECIPIENT,
                    User::STATUS_PENDING_APPROVAL, User::MEMBERSHIP_PROVIDER,
                    User::MEMBERSHIP_PROVIDER, User::STATUS_ACTIVE,
                    User::MEMBERSHIP_PROVIDER,
                ]
            )
            ->first();

        // 3 individual Request counts → 1 aggregate query
        $requestStats = RequestModel::query()
            ->selectRaw(
                "SUM(CASE WHEN status IN ('REQUESTED','APPROVED','REDEEMABLE') THEN 1 ELSE 0 END) AS open_requests,
                 SUM(CASE WHEN status = 'REQUESTED' THEN 1 ELSE 0 END)                           AS awaiting_assignment,
                 SUM(CASE WHEN status IN ('APPROVED','REDEEMABLE') THEN 1 ELSE 0 END)            AS in_progress"
            )
            ->first();

        return [
            [
                'key' => 'pending_approvals',
                'label_key' => 'dashboard.kpi.pending_approvals.label',
                'value' => (int) ($userStats->pending_total ?? 0),
                'value_format' => 'integer',
                'sub_key' => 'dashboard.kpi.pending_approvals.sub',
                'sub_params' => [
                    'recipients' => (int) ($userStats->pending_recipients ?? 0),
                    'providers' => (int) ($userStats->pending_providers ?? 0),
                ],
                'icon' => 'fa-solid fa-user-clock',
                'color' => 'amber',
                'route' => 'admin.users.pending',
                'action_key' => 'dashboard.kpi.pending_approvals.action',
            ],
            [
                'key' => 'open_requests',
                'label_key' => 'dashboard.kpi.open_requests.label',
                'value' => (int) ($requestStats->open_requests ?? 0),
                'value_format' => 'integer',
                'sub_key' => 'dashboard.kpi.open_requests.sub',
                'sub_params' => [
                    'awaiting' => (int) ($requestStats->awaiting_assignment ?? 0),
                    'in_progress' => (int) ($requestStats->in_progress ?? 0),
                ],
                'icon' => 'fa-solid fa-inbox',
                'color' => 'blue',
                'route' => 'admin.requests.index',
                'action_key' => 'dashboard.kpi.open_requests.action',
            ],
            [
                'key' => 'wallet_balance',
                'label_key' => 'dashboard.kpi.wallet_balance.label',
                'value' => $financial['system_wallet_balance'],
                'value_format' => 'currency',
                'sub_key' => 'dashboard.kpi.wallet_balance.sub',
                'sub_params' => [],
                'icon' => 'fa-solid fa-wallet',
                'color' => 'green',
                'route' => 'admin.finances.overview',
                'action_key' => 'dashboard.kpi.wallet_balance.action',
            ],
            [
                'key' => 'approved_providers',
                'label_key' => 'dashboard.kpi.approved_providers.label',
                'value' => (int) ($userStats->approved_providers ?? 0),
                'value_format' => 'integer',
                'sub_key' => 'dashboard.kpi.approved_providers.sub',
                'sub_params' => ['total' => (int) ($userStats->total_providers ?? 0)],
                'icon' => 'fa-solid fa-store',
                'color' => 'purple',
                'route' => 'admin.manage.users.index',
                'action_key' => 'dashboard.kpi.approved_providers.action',
            ],
        ];
    }

    // ── Platform snapshot (privacy-safe aggregates only) ──────────────────────

    private function buildPlatformSnapshot(): array
    {
        // 6 individual User counts → 1 aggregate query
        $userCounts = User::query()
            ->selectRaw(
                'COUNT(*)                                                                    AS total,
                 SUM(CASE WHEN membership_type = ? THEN 1 ELSE 0 END)                      AS donors,
                 SUM(CASE WHEN membership_type = ? THEN 1 ELSE 0 END)                      AS recipients,
                 SUM(CASE WHEN membership_type = ? THEN 1 ELSE 0 END)                      AS providers,
                 SUM(CASE WHEN membership_type = ? AND status = ? THEN 1 ELSE 0 END)       AS approved_providers,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END)                               AS pending_users',
                [
                    User::MEMBERSHIP_DONOR,
                    User::MEMBERSHIP_RECIPIENT,
                    User::MEMBERSHIP_PROVIDER,
                    User::MEMBERSHIP_PROVIDER, User::STATUS_ACTIVE,
                    User::STATUS_PENDING_APPROVAL,
                ]
            )
            ->first();

        // 2 individual Request counts → 1 aggregate query
        $cutoff = now()->subDays(30);
        $requestCounts = RequestModel::query()
            ->selectRaw(
                "SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END)                          AS requests_30d,
                 SUM(CASE WHEN status = 'FULFILLED' AND updated_at >= ? THEN 1 ELSE 0 END) AS fulfilled_30d",
                [$cutoff, $cutoff]
            )
            ->first();

        return [
            'total_users' => (int) ($userCounts->total ?? 0),
            'donors' => (int) ($userCounts->donors ?? 0),
            'recipients' => (int) ($userCounts->recipients ?? 0),
            'providers' => (int) ($userCounts->providers ?? 0),
            'approved_providers' => (int) ($userCounts->approved_providers ?? 0),
            'pending_users' => (int) ($userCounts->pending_users ?? 0),
            'requests_30d' => (int) ($requestCounts->requests_30d ?? 0),
            'fulfilled_30d' => (int) ($requestCounts->fulfilled_30d ?? 0),
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
                'id' => $a->id,
                'description' => $a->description,
                'log_name' => $a->log_name,
                'causer_name' => $a->causer?->name ?? 'System',
                'created_at' => $a->created_at,
            ])
            ->toArray();
    }
}
