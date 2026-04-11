<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Laravel built-in maintenance mode (php artisan down / up) with --secret bypass.
 * No custom maintenance middleware — uses framework defaults only.
 */
class MaintenanceSettingsController extends Controller
{
    public const SETTING_BYPASS_SECRET = 'app.maintenance.bypass_secret';

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function edit(Request $request): View
    {
        $active = $this->isMaintenanceFilePresent();
        $secret = SystemSetting::getValue(self::SETTING_BYPASS_SECRET);
        $bypassUrl = ($active && $secret !== null && $secret !== '')
            ? $this->bypassUrl($secret)
            : null;

        return view('admin.settings.maintenance', [
            'maintenanceActive' => $active,
            'bypassUrl' => $bypassUrl,
        ]);
    }

    public function enable(Request $request): View|RedirectResponse
    {
        if ($this->isMaintenanceFilePresent()) {
            return redirect()
                ->route('admin.settings.maintenance.edit')
                ->with('warning', __('Maintenance mode is already enabled.'));
        }

        $secret = Str::random(48);

        $exitCode = Artisan::call('down', ['--secret' => $secret]);

        if ($exitCode !== 0) {
            return redirect()
                ->route('admin.settings.maintenance.edit')
                ->with('error', __('Could not enable maintenance mode. Check server permissions on storage/framework.'));
        }

        SystemSetting::setValue(self::SETTING_BYPASS_SECRET, $secret);

        $this->auditService->log('maintenance', 'enabled', [
            'decision' => 'enable',
            'bypass_secret_stored' => true,
        ], $request->user()->id);

        $bypassUrl = $this->bypassUrl($secret);

        return view('admin.settings.maintenance-enabled', [
            'bypassUrl' => $bypassUrl,
            'settingsUrl' => route('admin.settings.maintenance.edit'),
        ]);
    }

    public function disable(Request $request): RedirectResponse
    {
        if (! $this->isMaintenanceFilePresent()) {
            SystemSetting::query()->where('key', self::SETTING_BYPASS_SECRET)->delete();

            return redirect()
                ->route('admin.settings.maintenance.edit')
                ->with('info', __('Maintenance mode is already off.'));
        }

        $exitCode = Artisan::call('up');

        if ($exitCode !== 0) {
            return redirect()
                ->route('admin.settings.maintenance.edit')
                ->with('error', __('Could not disable maintenance mode.'));
        }

        SystemSetting::query()->where('key', self::SETTING_BYPASS_SECRET)->delete();

        $this->auditService->log('maintenance', 'disabled', [
            'decision' => 'disable',
        ], $request->user()->id);

        return redirect()
            ->route('admin.settings.maintenance.edit')
            ->with('success', __('Maintenance mode has been disabled. The site is live again.'));
    }

    private function isMaintenanceFilePresent(): bool
    {
        return file_exists(storage_path('framework/maintenance.php'));
    }

    private function bypassUrl(string $secret): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($secret, '/');
    }
}
