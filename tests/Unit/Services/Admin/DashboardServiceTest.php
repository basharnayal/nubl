<?php

namespace Tests\Unit\Services\Admin;

use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\Admin\Dashboard\AttentionQueueBuilder;
use App\Services\Admin\Dashboard\SystemStatusChecker;
use App\Services\Admin\DashboardService;
use App\Services\Admin\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function get_overview_returns_expected_structure_and_kpi_counts(): void
    {
        $adminCauser = User::factory()->create(['name' => 'Admin Actor']);

        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);
        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 20,
            'status' => 'REQUESTED',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 30,
            'status' => 'APPROVED',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 40,
            'status' => 'REDEEMABLE',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50,
            'status' => 'FULFILLED',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Activity::create([
                'log_name' => 'audit',
                'description' => "event-{$i}",
                'causer_type' => User::class,
                'causer_id' => $adminCauser->id,
                'properties' => [],
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $statusChecker = Mockery::mock(SystemStatusChecker::class);
        $statusChecker->shouldReceive('all')
            ->once()
            ->andReturn([['key' => 'gateway', 'is_ok' => true]]);

        $attentionQueue = Mockery::mock(AttentionQueueBuilder::class);
        $attentionQueue->shouldReceive('build')
            ->once()
            ->andReturn([['severity' => 'medium', 'labelKey' => 'dashboard.attention.pending_approvals.label']]);

        $financialService = Mockery::mock(FinancialService::class);
        $financialService->shouldReceive('getOverview')
            ->once()
            ->andReturn([
                'system_wallet_balance' => 1234.56,
                'successful_payments_count' => 0,
                'successful_payments_amount' => 0.0,
                'pending_count' => 0,
                'pending_amount' => 0.0,
                'failed_count' => 0,
                'failed_amount' => 0.0,
                'fund_inbound_system' => 0.0,
                'fund_outbound_system' => 0.0,
                'transfers_to_providers' => 0.0,
            ]);

        $service = new DashboardService($statusChecker, $attentionQueue, $financialService);
        $overview = $service->getOverview();

        $this->assertArrayHasKey('system_status', $overview);
        $this->assertArrayHasKey('attention_items', $overview);
        $this->assertArrayHasKey('kpis', $overview);
        $this->assertArrayHasKey('financial', $overview);
        $this->assertArrayHasKey('platform', $overview);
        $this->assertArrayHasKey('recent_activity', $overview);

        $kpis = collect($overview['kpis'])->keyBy('key');
        $this->assertSame(2, $kpis['pending_approvals']['value']);
        $this->assertSame(3, $kpis['open_requests']['value']);
        $this->assertSame(1234.56, $kpis['wallet_balance']['value']);
        $this->assertSame(3, $kpis['approved_providers']['value']);

        $platform = $overview['platform'];
        $this->assertSame(User::count(), $platform['total_users']);
        $this->assertSame(1, $platform['fulfilled_30d']);
        $this->assertSame(4, $platform['requests_30d']);

        $this->assertCount(8, $overview['recent_activity']);
        $this->assertSame('event-10', $overview['recent_activity'][0]['description']);
        $this->assertSame('Admin Actor', $overview['recent_activity'][0]['causer_name']);
    }
}
