<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use App\Services\RecipientAllowanceService;
use App\Support\WeeklyAllowanceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR-17.1: Administrator-configurable system-wide weekly allowance (scheduled for next week).
 */
class AllowanceSettingsController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()->can('allowances.configure'), 403);

        return view(
            'admin.settings.allowance',
            RecipientAllowanceService::adminAllowanceSettingsPageData()
        );
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('allowances.configure'), 403);

        $min = (int) config('recipient.weekly_allowance_limit_min', 1);
        $max = (int) config('recipient.weekly_allowance_limit_max', 100_000);

        $validated = $request->validate([
            'weekly_allowance_sar' => ['required', 'numeric', 'min:'.$min, 'max:'.$max],
        ]);

        RecipientAllowanceService::schedulePendingWeeklyAllowanceChange(
            (float) $validated['weekly_allowance_sar'],
            $request->user()
        );

        $this->auditService->log('allowance_settings', 'weekly_limit_scheduled', [
            'decision' => 'schedule_pending_weekly_allowance',
            'weekly_allowance_sar' => (float) $validated['weekly_allowance_sar'],
            'effective_at' => SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT),
        ], $request->user()->id);

        return back()->with('success', __('Weekly allowance update scheduled.'));
    }
}
