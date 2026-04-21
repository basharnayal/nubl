<?php

namespace Tests\Unit\Services\Admin;

use App\Models\Ewallet;
use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\Request as RequestModel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Admin\Dashboard\AttentionQueueBuilder;
use App\Support\WeeklyAllowanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttentionQueueBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected string $maintenancePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenancePath = storage_path('framework/maintenance.php');
        if (file_exists($this->maintenancePath)) {
            unlink($this->maintenancePath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->maintenancePath)) {
            unlink($this->maintenancePath);
        }

        parent::tearDown();
    }

    #[Test]
    public function builder_returns_expected_items_sorted_by_severity(): void
    {
        config(['services.myfatoorah.api_key' => null]);

        if (! is_dir(dirname($this->maintenancePath))) {
            mkdir(dirname($this->maintenancePath), 0777, true);
        }
        file_put_contents($this->maintenancePath, '<?php return [];');

        $donor = User::factory()->create();
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'FAILED-TODAY',
            'status' => Payment::STATUS_FAILED,
            'amount' => 100,
            'updated_at' => now(),
        ]);
        $stalePayment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'STALE-PENDING',
            'status' => Payment::STATUS_PENDING,
            'amount' => 100,
        ]);
        $stalePayment->created_at = now()->subHours(30);
        $stalePayment->updated_at = now()->subHours(30);
        $stalePayment->save();

        [$provider, $wallet] = $this->createProviderWithWallet();

        $overduePayout = ProviderPayout::create([
            'provider_id' => $provider->id,
            'provider_wallet_id' => $wallet->id,
            'week_start_at' => now()->subWeek(),
            'week_end_at' => now()->subDays(1),
            'scheduled_at' => now()->subHours(10),
            'amount' => 250,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
        ]);
        $overduePayout->created_at = now()->subHours(72);
        $overduePayout->updated_at = now()->subHours(72);
        $overduePayout->save();

        $pendingPayout = ProviderPayout::create([
            'provider_id' => $provider->id,
            'provider_wallet_id' => $wallet->id,
            'week_start_at' => now()->subWeek(),
            'week_end_at' => now()->subDays(1),
            'scheduled_at' => now(),
            'amount' => 120,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
        ]);
        $pendingPayout->created_at = now()->subHours(6);
        $pendingPayout->updated_at = now()->subHours(6);
        $pendingPayout->save();

        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
        User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 20,
            'status' => 'REQUESTED',
        ]);

        SystemSetting::setValue('allocation_engine.paused', '1');
        // Keep allowance unset to trigger allowance_not_set item

        $items = (new AttentionQueueBuilder)->build();
        $labelKeys = array_column($items, 'labelKey');
        $severities = array_column($items, 'severity');

        $this->assertContains('dashboard.attention.maintenance_on.label', $labelKeys);
        $this->assertContains('dashboard.attention.gateway_missing.label', $labelKeys);
        $this->assertContains('dashboard.attention.failed_payments.label', $labelKeys);
        $this->assertContains('dashboard.attention.overdue_payouts.label', $labelKeys);
        $this->assertContains('dashboard.attention.pending_approvals.label', $labelKeys);
        $this->assertContains('dashboard.attention.new_requests.label', $labelKeys);
        $this->assertContains('dashboard.attention.pending_payouts.label', $labelKeys);
        $this->assertContains('dashboard.attention.stale_payments.label', $labelKeys);
        $this->assertContains('dashboard.attention.allocation_paused.label', $labelKeys);
        $this->assertContains('dashboard.attention.allowance_not_set.label', $labelKeys);

        $firstMedium = array_search('medium', $severities, true);
        $lastHigh = array_key_last(array_filter($severities, fn ($severity) => $severity === 'high'));

        $this->assertNotFalse($firstMedium);
        $this->assertNotNull($lastHigh);
        $this->assertGreaterThan($lastHigh, $firstMedium);
    }

    #[Test]
    public function builder_returns_empty_list_when_no_attention_conditions_match(): void
    {
        config(['services.myfatoorah.api_key' => 'configured']);
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_ACTIVE, '300');
        SystemSetting::setValue('allocation_engine.paused', '0');

        $items = (new AttentionQueueBuilder)->build();

        $this->assertSame([], $items);
    }

    /**
     * @return array{User, Ewallet}
     */
    private function createProviderWithWallet(): array
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $profile = $provider->providerProfile()->create([
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '966511111111',
            'email' => $provider->email,
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'unified_number' => '700000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
        ]);

        $wallet = Ewallet::query()
            ->where('owner_type', 'PROVIDER')
            ->where('owner_id', $profile->id)
            ->firstOrFail();

        return [$provider, $wallet];
    }
}
