<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\Request as RequestModel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\WeeklyAllowanceSettings;

class AttentionQueueBuilder
{
    public function build(): array
    {
        $items = array_filter([
            $this->maintenanceModeItem(),
            $this->gatewayMissingItem(),
            $this->failedPaymentsTodayItem(),
            $this->overduePayoutsItem(),
            $this->pendingApprovalsItem(),
            $this->newRequestsItem(),
            $this->pendingPayoutsItem(),
            $this->stalePaymentsItem(),
            $this->allocationPausedItem(),
            $this->allowanceNotSetItem(),
        ]);

        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($items, fn ($a, $b) => $order[$a['severity']] <=> $order[$b['severity']]);

        return array_values($items);
    }

    // High severity

    private function maintenanceModeItem(): ?array
    {
        if (! file_exists(storage_path('framework/maintenance.php'))) {
            return null;
        }

        return $this->item(
            severity: 'high',
            icon: 'fa-solid fa-triangle-exclamation',
            labelKey: 'dashboard.attention.maintenance_on.label',
            count: null,
            descKey: 'dashboard.attention.maintenance_on.desc',
            descParams: [],
            actionKey: 'dashboard.attention.maintenance_on.action',
            actionRoute: 'admin.settings.maintenance.edit',
        );
    }

    private function gatewayMissingItem(): ?array
    {
        if (! empty(config('services.myfatoorah.api_key'))) {
            return null;
        }

        return $this->item(
            severity: 'high',
            icon: 'fa-solid fa-credit-card',
            labelKey: 'dashboard.attention.gateway_missing.label',
            count: null,
            descKey: 'dashboard.attention.gateway_missing.desc',
            descParams: [],
            actionKey: null,
            actionRoute: null,
        );
    }

    private function failedPaymentsTodayItem(): ?array
    {
        $count = Payment::where('status', Payment::STATUS_FAILED)
            ->whereDate('updated_at', today())
            ->count();

        if ($count === 0) {
            return null;
        }

        return $this->item(
            severity: 'high',
            icon: 'fa-solid fa-circle-xmark',
            labelKey: 'dashboard.attention.failed_payments.label',
            count: $count,
            descKey: 'dashboard.attention.failed_payments.desc',
            descParams: [],
            actionKey: 'dashboard.attention.failed_payments.action',
            actionRoute: 'admin.finances.payments.index',
        );
    }

    private function overduePayoutsItem(): ?array
    {
        $count = ProviderPayout::where('status', ProviderPayout::STATUS_PENDING_ADMIN_REVIEW)
            ->where('created_at', '<', now()->subHours(48))
            ->count();

        if ($count === 0) {
            return null;
        }

        return $this->item(
            severity: 'high',
            icon: 'fa-solid fa-clock-rotate-left',
            labelKey: 'dashboard.attention.overdue_payouts.label',
            count: $count,
            descKey: 'dashboard.attention.overdue_payouts.desc',
            descParams: [],
            actionKey: 'dashboard.attention.overdue_payouts.action',
            actionRoute: 'admin.finances.provider-payouts.index',
        );
    }

    // Medium severity

    private function pendingApprovalsItem(): ?array
    {
        $total = User::where('status', User::STATUS_PENDING_APPROVAL)->count();

        if ($total === 0) {
            return null;
        }

        $recipients = User::where('status', User::STATUS_PENDING_APPROVAL)
            ->where('membership_type', User::MEMBERSHIP_RECIPIENT)->count();
        $providers = User::where('status', User::STATUS_PENDING_APPROVAL)
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)->count();

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-user-clock',
            labelKey: 'dashboard.attention.pending_approvals.label',
            count: $total,
            descKey: 'dashboard.attention.pending_approvals.desc',
            descParams: ['recipients' => $recipients, 'providers' => $providers],
            actionKey: 'dashboard.attention.pending_approvals.action',
            actionRoute: 'admin.users.pending',
        );
    }

    private function newRequestsItem(): ?array
    {
        $count = RequestModel::where('status', 'REQUESTED')->count();

        if ($count === 0) {
            return null;
        }

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-inbox',
            labelKey: 'dashboard.attention.new_requests.label',
            count: $count,
            descKey: 'dashboard.attention.new_requests.desc',
            descParams: [],
            actionKey: 'dashboard.attention.new_requests.action',
            actionRoute: 'admin.requests.index',
        );
    }

    private function pendingPayoutsItem(): ?array
    {
        $count = ProviderPayout::where('status', ProviderPayout::STATUS_PENDING_ADMIN_REVIEW)
            ->where('created_at', '>=', now()->subHours(48))
            ->count();

        if ($count === 0) {
            return null;
        }

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-money-bill-transfer',
            labelKey: 'dashboard.attention.pending_payouts.label',
            count: $count,
            descKey: 'dashboard.attention.pending_payouts.desc',
            descParams: [],
            actionKey: 'dashboard.attention.pending_payouts.action',
            actionRoute: 'admin.finances.provider-payouts.index',
        );
    }

    private function stalePaymentsItem(): ?array
    {
        $pendingStatuses = [Payment::STATUS_INITIATED, Payment::STATUS_PENDING, Payment::STATUS_PROCESSING];

        $count = Payment::whereIn('status', $pendingStatuses)
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        if ($count === 0) {
            return null;
        }

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-hourglass-half',
            labelKey: 'dashboard.attention.stale_payments.label',
            count: $count,
            descKey: 'dashboard.attention.stale_payments.desc',
            descParams: [],
            actionKey: 'dashboard.attention.stale_payments.action',
            actionRoute: 'admin.finances.payments.index',
        );
    }

    private function allocationPausedItem(): ?array
    {
        if (SystemSetting::getValue('allocation_engine.paused') !== '1') {
            return null;
        }

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-circle-pause',
            labelKey: 'dashboard.attention.allocation_paused.label',
            count: null,
            descKey: 'dashboard.attention.allocation_paused.desc',
            descParams: [],
            actionKey: 'dashboard.attention.allocation_paused.action',
            actionRoute: 'admin.allocation.status',
        );
    }

    private function allowanceNotSetItem(): ?array
    {
        $value = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_ACTIVE);

        if ($value !== null && (float) $value > 0) {
            return null;
        }

        return $this->item(
            severity: 'medium',
            icon: 'fa-solid fa-hand-holding-heart',
            labelKey: 'dashboard.attention.allowance_not_set.label',
            count: null,
            descKey: 'dashboard.attention.allowance_not_set.desc',
            descParams: [],
            actionKey: 'dashboard.attention.allowance_not_set.action',
            actionRoute: 'admin.settings.allowances.edit',
        );
    }

    // Helper

    private function item(
        string $severity,
        string $icon,
        string $labelKey,
        ?int $count,
        string $descKey,
        array $descParams,
        ?string $actionKey,
        ?string $actionRoute,
    ): array {
        return compact(
            'severity', 'icon', 'labelKey', 'count',
            'descKey', 'descParams', 'actionKey', 'actionRoute'
        );
    }
}
