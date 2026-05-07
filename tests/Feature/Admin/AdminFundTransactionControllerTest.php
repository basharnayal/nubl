<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFundTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Ewallet $systemWallet;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'donor', 'provider', 'recipient'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
    }

    #[Test]
    public function index_displays_fund_transactions_with_applied_filters(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create(['amount' => 18]);

        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 18.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.index', [
                'search' => (string) $payment->id,
                'direction' => FundTransaction::DIRECTION_IN,
                'wallet_type' => 'SYSTEM',
                'donor_id' => $donor->id,
            ]));

        $response->assertOk();
        $response->assertViewIs('admin.finances.fund-transactions.index');
        $response->assertSee((string) $transaction->id, false);
    }

    #[Test]
    public function show_returns_empty_audit_entries_when_transaction_has_no_payment_or_request_link(): void
    {
        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 10.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.show', $transaction));

        $response->assertOk();
        $response->assertViewIs('admin.finances.fund-transactions.show');
        $response->assertViewHas('auditEntries', function ($entries): bool {
            return $entries instanceof Collection && $entries->isEmpty();
        });
    }

    #[Test]
    public function show_collects_audit_entries_when_payment_and_request_are_present(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create(['amount' => 33]);
        $request = $this->createRequestRecord();

        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 33.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);

        $matchPayment = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => $payment->id])
            ->log('finance.payment_linked');
        $matchRequest = activity()
            ->causedBy($this->admin)
            ->withProperties(['request_id' => $request->id])
            ->log('request.status_changed');
        $unrelated = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => 999999, 'request_id' => 888888])
            ->log('finance.unrelated');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.show', $transaction));

        $response->assertOk();
        $response->assertViewHas('auditEntries', function ($entries) use ($matchPayment, $matchRequest, $unrelated): bool {
            if (! $entries instanceof Collection) {
                return false;
            }
            $ids = $entries->pluck('id')->all();

            return in_array($matchPayment->id, $ids, true)
                && in_array($matchRequest->id, $ids, true)
                && ! in_array($unrelated->id, $ids, true);
        });
    }

    #[Test]
    public function show_collects_only_payment_audit_entries_when_only_payment_link_exists(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create(['amount' => 21]);

        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 21.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $matchPayment = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => $payment->id])
            ->log('finance.payment_only');
        $nonMatchRequest = activity()
            ->causedBy($this->admin)
            ->withProperties(['request_id' => 123456])
            ->log('request.other');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.show', $transaction));

        $response->assertOk();
        $response->assertViewHas('auditEntries', function ($entries) use ($matchPayment, $nonMatchRequest): bool {
            if (! $entries instanceof Collection) {
                return false;
            }
            $ids = $entries->pluck('id')->all();

            return in_array($matchPayment->id, $ids, true)
                && ! in_array($nonMatchRequest->id, $ids, true);
        });
    }

    #[Test]
    public function show_collects_only_request_audit_entries_when_only_request_link_exists(): void
    {
        $request = $this->createRequestRecord();

        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_REDEMPTION,
            'amount' => 19.00,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => null,
            'request_id' => $request->id,
            'order_redemption_id' => null,
        ]);

        $matchRequest = activity()
            ->causedBy($this->admin)
            ->withProperties(['request_id' => $request->id])
            ->log('request.only');
        $nonMatchPayment = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => 987654])
            ->log('finance.other_payment');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.show', $transaction));

        $response->assertOk();
        $response->assertViewHas('auditEntries', function ($entries) use ($matchRequest, $nonMatchPayment): bool {
            if (! $entries instanceof Collection) {
                return false;
            }
            $ids = $entries->pluck('id')->all();

            return in_array($matchRequest->id, $ids, true)
                && ! in_array($nonMatchPayment->id, $ids, true);
        });
    }

    #[Test]
    public function export_streams_csv_content_and_writes_audit_entry(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create(['amount' => 15]);
        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 15.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export', [
                'direction' => FundTransaction::DIRECTION_IN,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('wallet_owner_type', $csv);
        $this->assertStringContainsString((string) $transaction->id, $csv);
        $this->assertStringContainsString('SYSTEM', $csv);

        $activity = Activity::query()
            ->where('description', 'finance.fund_transactions_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('fund_transactions_csv', $activity->properties->get('export_type'));
        $this->assertSame(FundTransaction::DIRECTION_IN, $activity->properties->get('filters')['direction'] ?? null);
    }

    #[Test]
    public function show_displays_guest_donor_for_guest_payment_transaction(): void
    {
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 25.00,
        ]);

        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 25.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $guestPayment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.show', $transaction));

        $response->assertOk();
        $response->assertSee('متبرع زائر', false);
        $response->assertSee('تبرع سريع', false);
    }

    #[Test]
    public function export_csv_includes_guest_donor_label_for_guest_payment_transactions(): void
    {
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 31.00,
        ]);
        $transaction = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 31.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $guestPayment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('donor_name', $csv);
        $this->assertStringContainsString((string) $transaction->id, $csv);
        $this->assertStringContainsString('متبرع زائر', $csv);
    }

    #[Test]
    public function export_pdf_includes_guest_donor_label_for_guest_payment_transactions(): void
    {
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 47.00,
        ]);
        FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 47.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $guestPayment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $rendered = view('admin.finances.exports.fund-transactions-pdf', [
            'transactions' => FundTransaction::query()
                ->with(['wallet', 'sponsor', 'payment'])
                ->get(),
            'generated_at' => now(),
        ])->render();

        $this->assertStringContainsString('متبرع زائر', $rendered);
    }

    #[Test]
    public function export_pdf_downloads_and_writes_audit_entry(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create(['amount' => 42]);
        FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 42.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.fund-transactions.export-pdf', [
                'source' => FundTransaction::SOURCE_DONATION,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $activity = Activity::query()
            ->where('description', 'finance.fund_transactions_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('fund_transactions_pdf', $activity->properties->get('export_type'));
        $this->assertSame(FundTransaction::SOURCE_DONATION, $activity->properties->get('filters')['source'] ?? null);
    }

    private function createDonor(): User
    {
        $donor = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor->assignRole('donor');

        return $donor;
    }

    private function createRequestRecord(): RequestModel
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $provider->assignRole('provider');

        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $recipient->assignRole('recipient');

        return RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 20.00,
            'funding_source' => 'CITY_FUND',
            'status' => 'REQUESTED',
        ]);
    }
}
