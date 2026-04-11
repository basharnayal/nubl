<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use App\Support\QrTtl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR-8.3: Administrator-configurable QR redemption TTL.
 */
class QrSettingsController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function edit(Request $request): View
    {
        abort_unless($request->user()->can('qr.configure_ttl'), 403);

        $ttlMinutes = QrTtl::currentMinutes();
        $min = (int) config('qr.ttl_minutes_min', 15);
        $max = (int) config('qr.ttl_minutes_max', 720);
        $default = (int) config('qr.ttl_minutes', 180);

        return view('admin.settings.qr', compact('ttlMinutes', 'min', 'max', 'default'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('qr.configure_ttl'), 403);

        $min = (int) config('qr.ttl_minutes_min', 15);
        $max = (int) config('qr.ttl_minutes_max', 720);

        $validated = $request->validate([
            'ttl_minutes' => ['required', 'integer', 'min:'.$min, 'max:'.$max],
        ]);

        SystemSetting::setValue('qr.ttl_minutes', (string) $validated['ttl_minutes']);

        $this->auditService->log('qr_settings', 'updated', [
            'decision' => 'save_ttl',
            'ttl_minutes' => (int) $validated['ttl_minutes'],
        ], $request->user()->id);

        return back()->with('success', __('Settings saved.'));
    }
}
