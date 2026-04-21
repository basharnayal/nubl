<?php

namespace Tests\Unit\Services;

use App\Contracts\AllocationEngineServiceInterface;
use App\Jobs\ProcessPendingAllocationsJob;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\AllocationEngineStatusChangedNotification;
use App\Services\AllocationEngineService;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllocationEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $actorAdmin;

    protected User $otherAdmin;

    protected User $providerA;

    protected User $providerB;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'provider'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->actorAdmin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->actorAdmin->assignRole('admin');

        $this->otherAdmin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->otherAdmin->assignRole('admin');

        $this->providerA = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->providerA->assignRole('provider');

        $this->providerB = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->providerB->assignRole('provider');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function service_interface_is_bound_to_allocation_engine_service(): void
    {
        $resolved = app(AllocationEngineServiceInterface::class);

        $this->assertInstanceOf(AllocationEngineService::class, $resolved);
    }

    #[Test]
    public function pause_global_updates_setting_audits_and_notifies(): void
    {
        Notification::fake();

        $audit = Mockery::spy(AuditService::class);
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationEngineService::class);
        $service->pauseGlobal($this->actorAdmin);

        $this->assertSame('1', SystemSetting::getValue('allocation_engine.paused'));

        $audit->shouldHaveReceived('log')
            ->with(
                'allocation_engine',
                'paused_globally',
                Mockery::on(fn (array $data): bool => ($data['admin_id'] ?? null) === $this->actorAdmin->id),
                $this->actorAdmin->id
            )
            ->once();

        Notification::assertSentTo(
            [$this->actorAdmin, $this->otherAdmin],
            AllocationEngineStatusChangedNotification::class,
            fn (AllocationEngineStatusChangedNotification $notification): bool => $notification->event === 'paused_globally'
        );

        Notification::assertSentTo(
            [$this->providerA, $this->providerB],
            AllocationEngineStatusChangedNotification::class,
            fn (AllocationEngineStatusChangedNotification $notification): bool => $notification->event === 'paused_globally_provider'
        );
    }

    #[Test]
    public function resume_global_updates_setting_dispatches_job_and_notifies(): void
    {
        Bus::fake();
        Notification::fake();

        SystemSetting::setValue('allocation_engine.paused', '1');

        $audit = Mockery::spy(AuditService::class);
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationEngineService::class);
        $service->resumeGlobal($this->actorAdmin);

        $this->assertSame('0', SystemSetting::getValue('allocation_engine.paused'));

        Bus::assertDispatched(
            ProcessPendingAllocationsJob::class,
            fn (ProcessPendingAllocationsJob $job): bool => $job->providerId === null
        );

        $audit->shouldHaveReceived('log')
            ->with(
                'allocation_engine',
                'resumed_globally',
                Mockery::on(fn (array $data): bool => ($data['admin_id'] ?? null) === $this->actorAdmin->id),
                $this->actorAdmin->id
            )
            ->once();

        Notification::assertSentTo(
            [$this->providerA, $this->providerB],
            AllocationEngineStatusChangedNotification::class,
            fn (AllocationEngineStatusChangedNotification $notification): bool => $notification->event === 'resumed_globally_provider'
        );
    }

    #[Test]
    public function pause_and_resume_provider_toggle_state_and_dispatch_provider_job(): void
    {
        Bus::fake();
        Notification::fake();

        $audit = Mockery::spy(AuditService::class);
        $this->app->instance(AuditService::class, $audit);

        $service = app(AllocationEngineService::class);

        $service->pauseProvider($this->providerA, $this->actorAdmin);
        $this->assertTrue((bool) $this->providerA->fresh()->allocation_paused);

        Notification::assertSentTo(
            $this->providerA,
            AllocationEngineStatusChangedNotification::class,
            fn (AllocationEngineStatusChangedNotification $notification): bool => $notification->event === 'paused'
        );

        $service->resumeProvider($this->providerA->fresh(), $this->actorAdmin);
        $this->assertFalse((bool) $this->providerA->fresh()->allocation_paused);

        Bus::assertDispatched(
            ProcessPendingAllocationsJob::class,
            fn (ProcessPendingAllocationsJob $job): bool => $job->providerId === $this->providerA->id
        );

        Notification::assertSentTo(
            $this->providerA,
            AllocationEngineStatusChangedNotification::class,
            fn (AllocationEngineStatusChangedNotification $notification): bool => $notification->event === 'resumed'
        );

        $audit->shouldHaveReceived('log')
            ->with(
                'allocation_engine',
                'paused_for_provider',
                Mockery::on(fn (array $data): bool => ($data['provider_id'] ?? null) === $this->providerA->id),
                $this->actorAdmin->id
            )
            ->once();
        $audit->shouldHaveReceived('log')
            ->with(
                'allocation_engine',
                'resumed_for_provider',
                Mockery::on(fn (array $data): bool => ($data['provider_id'] ?? null) === $this->providerA->id),
                $this->actorAdmin->id
            )
            ->once();
    }
}
