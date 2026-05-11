<?php

namespace App\Services\Admin\Dashboard;

use App\Models\SystemSetting;
use App\Support\QrTtl;
use App\Support\WeeklyAllowanceSettings;

class SystemStatusChecker
{
    public function all(): array
    {
        return [
            $this->maintenanceStatus(),
            $this->qrStatus(),
            $this->allowanceStatus(),
            $this->allocationStatus(),
            $this->paymentGatewayStatus(),
        ];
    }

    private function maintenanceStatus(): array
    {
        $active = file_exists(storage_path('framework/maintenance.php'));

        return [
            'key' => 'maintenance',
            'label_key' => 'dashboard.status.maintenance',
            'icon' => 'fa-solid fa-triangle-exclamation',
            'is_ok' => ! $active,
            'tooltip_key' => $active
                                    ? 'dashboard.status.tooltip.maintenance_on'
                                    : 'dashboard.status.tooltip.maintenance_off',
            'tooltip_params' => [],
            'route' => 'admin.settings.maintenance.edit',
            'severity' => $active ? 'high' : 'ok',
        ];
    }

    private function qrStatus(): array
    {
        $dbOverride = SystemSetting::getValue('qr.ttl_minutes');
        $configured = $dbOverride !== null && $dbOverride !== '';
        $minutes = QrTtl::currentMinutes();

        return [
            'key' => 'qr',
            'label_key' => 'dashboard.status.qr',
            'icon' => 'fa-solid fa-qrcode',
            'is_ok' => true,
            'tooltip_key' => $configured
                                    ? 'dashboard.status.tooltip.qr_configured'
                                    : 'dashboard.status.tooltip.qr_default',
            'tooltip_params' => ['minutes' => $minutes],
            'route' => 'admin.settings.qr.edit',
            'severity' => 'ok',
        ];
    }

    private function allowanceStatus(): array
    {
        $value = SystemSetting::getValue(WeeklyAllowanceSettings::KEY_ACTIVE);
        $configured = $value !== null && (float) $value > 0;

        return [
            'key' => 'allowance',
            'label_key' => 'dashboard.status.allowance',
            'icon' => 'fa-solid fa-hand-holding-heart',
            'is_ok' => $configured,
            'tooltip_key' => $configured
                                    ? 'dashboard.status.tooltip.allowance_on'
                                    : 'dashboard.status.tooltip.allowance_off',
            'tooltip_params' => $configured ? ['amount' => number_format((float) $value, 0)] : [],
            'route' => 'admin.settings.allowances.edit',
            'severity' => $configured ? 'ok' : 'warning',
        ];
    }

    private function allocationStatus(): array
    {
        $paused = SystemSetting::getValue('allocation_engine.paused') === '1';

        return [
            'key' => 'allocation',
            'label_key' => 'dashboard.status.allocation',
            'icon' => 'fa-solid fa-gears',
            'is_ok' => ! $paused,
            'tooltip_key' => $paused
                                    ? 'dashboard.status.tooltip.allocation_paused'
                                    : 'dashboard.status.tooltip.allocation_running',
            'tooltip_params' => [],
            'route' => 'admin.allocation.status',
            'severity' => $paused ? 'warning' : 'ok',
        ];
    }

    private function paymentGatewayStatus(): array
    {
        $apiKey = config('services.myfatoorah.api_key');
        $configured = ! empty($apiKey);

        return [
            'key' => 'gateway',
            'label_key' => 'dashboard.status.gateway',
            'icon' => 'fa-solid fa-credit-card',
            'is_ok' => $configured,
            'tooltip_key' => $configured
                                    ? 'dashboard.status.tooltip.gateway_on'
                                    : 'dashboard.status.tooltip.gateway_off',
            'tooltip_params' => [],
            'route' => null,
            'severity' => $configured ? 'ok' : 'high',
        ];
    }
}
