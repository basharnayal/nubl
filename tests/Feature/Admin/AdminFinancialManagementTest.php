<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFinancialManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $donor;

    protected Ewallet $systemWallet;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->admin->assignRole('admin');

        $this->donor = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $this->donor->assignRole('donor');

        $this->systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_financial_overview(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finances.overview'));

        $response->assertOk();
        $response->assertSee(__('finance.overview.system_wallet_balance'), false);
    }

    #[Test]
    public function donor_cannot_access_finance_module(): void
    {
        $routes = [
            route('admin.finances.overview'),
            route('admin.finances.payments.index'),
            route('admin.finances.fund-transactions.index'),
            route('admin.finances.reports.index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($this->donor)->get($url)->assertForbidden();
        }
    }

    #[Test]
    public function admin_can_view_payments_list_with_filters(): void
    {
        Payment::factory()->for($this->donor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.payments.index', [
            'status' => Payment::STATUS_SUCCEEDED,
            'donor_id' => $this->donor->id,
        ]));

        $response->assertOk();
        $response->assertSee('50', false);
    }

    #[Test]
    public function admin_can_view_fund_transactions_list(): void
    {
        $payment = Payment::factory()->for($this->donor, 'sponsor')->succeeded()->create(['amount' => 25]);

        FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $this->donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 25,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);
        $this->systemWallet->refresh();

        $response = $this->actingAs($this->admin)->get(route('admin.finances.fund-transactions.index'));

        $response->assertOk();
        $response->assertSee((string) $payment->id, false);
    }

    #[Test]
    public function admin_can_view_payment_details_with_links(): void
    {
        $payment = Payment::factory()->for($this->donor, 'sponsor')->succeeded()->create([
            'amount' => 40,
            'external_payment_id' => 'INV-123',
        ]);

        $ft = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $this->donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 40,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.payments.show', $payment));

        $response->assertOk();
        $response->assertSee('INV-123', false);
        $response->assertSee((string) $ft->id, false);
    }

    #[Test]
    public function failed_payments_have_no_fund_transactions_in_normal_flow(): void
    {
        $failed = Payment::factory()->for($this->donor, 'sponsor')->failed()->create(['amount' => 10]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.payments.show', $failed));

        $response->assertOk();
        $this->assertDatabaseMissing('fund_transactions', [
            'payment_id' => $failed->id,
        ]);
    }

    #[Test]
    public function overview_reflects_successful_payment_totals(): void
    {
        Payment::factory()->for($this->donor, 'sponsor')->count(2)->succeeded()->create(['amount' => 100]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.overview'));

        $response->assertOk();
        $response->assertSee('200.00', false);
    }

    #[Test]
    public function reports_use_payment_table_for_gateway_totals(): void
    {
        Payment::factory()->for($this->donor, 'sponsor')->succeeded()->create(['amount' => 75]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.reports.index', [
            'period' => 'custom',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('75.00', false);
    }

    #[Test]
    public function admin_payments_export_is_audited(): void
    {
        Payment::factory()->for($this->donor, 'sponsor')->succeeded()->create(['amount' => 10]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.payments.export', [
            'status' => Payment::STATUS_SUCCEEDED,
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'finance.payments_exported')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('export', $activity->properties->get('decision'));
        $this->assertSame(Payment::STATUS_SUCCEEDED, $activity->properties->get('filters')['status'] ?? null);
        $this->assertNotNull($activity->created_at);
    }

    #[Test]
    public function admin_fund_transactions_export_is_audited(): void
    {
        $payment = Payment::factory()->for($this->donor, 'sponsor')->succeeded()->create(['amount' => 15]);
        FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $this->donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 15,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.fund-transactions.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'finance.fund_transactions_exported')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame('export', $activity->properties->get('decision'));
        $this->assertNotNull($activity->created_at);
    }
}
