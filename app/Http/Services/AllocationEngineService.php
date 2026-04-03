<?php

namespace App\Http\Services;

use App\Contracts\AllocationEngineServiceInterface;
use App\Jobs\ProcessPendingAllocationsJob;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\AllocationEngineStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * FR-24.1: Pause/resume the allocation engine globally or per-provider.
 * FR-24.2: On resume, dispatch job to process any pending (queued) allocations.
 */
class AllocationEngineService implements AllocationEngineServiceInterface
{
    private const GLOBAL_PAUSE_KEY = 'allocation_engine.paused';

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function pauseGlobal(User $admin): void
    {
        SystemSetting::setValue(self::GLOBAL_PAUSE_KEY, '1');

        $this->auditService->log('allocation_engine', 'paused_globally', [
            'admin_id' => $admin->id,
        ], $admin->id);

        $this->notifyAdmins('paused_globally', $admin);
        $this->notifyAllProviders('paused_globally_provider', $admin);
    }

    public function resumeGlobal(User $admin): void
    {
        SystemSetting::setValue(self::GLOBAL_PAUSE_KEY, '0');

        $this->auditService->log('allocation_engine', 'resumed_globally', [
            'admin_id' => $admin->id,
        ], $admin->id);

        // FR-24.2: process all pending allocations (global + provider-level paused)
        ProcessPendingAllocationsJob::dispatch();

        $this->notifyAdmins('resumed_globally', $admin);
        $this->notifyAllProviders('resumed_globally_provider', $admin);
    }

    public function pauseProvider(User $provider, User $admin): void
    {
        $provider->update(['allocation_paused' => true]);

        $this->auditService->log('allocation_engine', 'paused_for_provider', [
            'provider_id' => $provider->id,
            'admin_id' => $admin->id,
        ], $admin->id);

        $provider->notify(new AllocationEngineStatusChangedNotification('paused', $admin));
        $this->notifyAdmins('paused_for_provider', $admin, $provider);
    }

    public function resumeProvider(User $provider, User $admin): void
    {
        $provider->update(['allocation_paused' => false]);

        $this->auditService->log('allocation_engine', 'resumed_for_provider', [
            'provider_id' => $provider->id,
            'admin_id' => $admin->id,
        ], $admin->id);

        // FR-24.2: process pending allocations for this provider only
        ProcessPendingAllocationsJob::dispatch($provider->id);

        $provider->notify(new AllocationEngineStatusChangedNotification('resumed', $admin));
        $this->notifyAdmins('resumed_for_provider', $admin, $provider);
    }

    public function isGloballyPaused(): bool
    {
        return SystemSetting::getValue(self::GLOBAL_PAUSE_KEY) === '1';
    }

    public function isProviderPaused(User $provider): bool
    {
        return (bool) $provider->allocation_paused;
    }

    private function notifyAdmins(string $event, User $actor, ?User $targetProvider = null): void
    {
        User::role('admin')->get()->each(function (User $admin) use ($event, $actor, $targetProvider) {
            $admin->notify(new AllocationEngineStatusChangedNotification($event, $actor, $targetProvider));
        });
    }

    private function notifyAllProviders(string $event, User $actor): void
    {
        $providers = User::role('provider')->get();
        if ($providers->isEmpty()) {
            return;
        }

        Notification::send($providers, new AllocationEngineStatusChangedNotification($event, $actor));
    }
}
