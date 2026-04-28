<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPaymentControllerTest extends TestCase
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
    public function index_displays_filtered_payments_for_admin(): void
    {
        $donor = $this->createDonor();

        Payment::factory()->for($donor, 'sponsor')->create([
            'status' => Payment::STATUS_FAILED,
            'amount' => 12.00,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'PAY-FAIL-1',
        ]);
        $matching = Payment::factory()->for($donor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 55.00,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'PAY-OK-100',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.payments.index', [
            'status' => Payment::STATUS_SUCCEEDED,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'donor_id' => $donor->id,
            'search' => (string) $matching->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.finances.payments.index');
        $response->assertSee((string) $matching->id, false);
        $response->assertSee('55.00', false);
        $response->assertDontSee('PAY-FAIL-1', false);
    }

    #[Test]
    public function index_and_show_display_guest_donor_for_guest_payments(): void
    {
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 33.00,
            'external_payment_id' => 'GUEST-ADMIN-1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.index', [
                'search' => (string) $guestPayment->id,
            ]))
            ->assertOk()
            ->assertSee('متبرع زائر', false)
            ->assertSee('تبرع سريع', false)
            ->assertSee('GUEST-ADMIN-1', false);

        $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.show', $guestPayment))
            ->assertOk()
            ->assertSee('متبرع زائر', false)
            ->assertSee('تبرع سريع', false);
    }

    #[Test]
    public function show_includes_related_records_and_payment_audit_entries_only(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create([
            'amount' => 90.00,
            'external_payment_id' => 'INV-SHOW-90',
        ]);

        $request = $this->createRequestRecord();
        $fundTx = FundTransaction::create([
            'wallet_id' => $this->systemWallet->id,
            'sponsor_id' => $donor->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 90.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $payment->id,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);
        RequestPaymentLink::create([
            'payment_id' => $payment->id,
            'request_id' => $request->id,
            'amount' => 20.00,
        ]);

        $matchingAudit = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => $payment->id])
            ->log('finance.payment_status_synced');
        $otherAudit = activity()
            ->causedBy($this->admin)
            ->withProperties(['payment_id' => 888888])
            ->log('finance.other');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.show', $payment));

        $response->assertOk();
        $response->assertViewIs('admin.finances.payments.show');
        $response->assertSee((string) $fundTx->id, false);
        $response->assertSee((string) $request->id, false);
        $response->assertViewHas('auditEntries', function ($entries) use ($matchingAudit, $otherAudit): bool {
            if (! $entries instanceof Collection) {
                return false;
            }
            $ids = $entries->pluck('id')->all();

            return in_array($matchingAudit->id, $ids, true)
                && ! in_array($otherAudit->id, $ids, true);
        });
    }

    #[Test]
    public function export_streams_csv_content_and_writes_audit_entry(): void
    {
        $donor = $this->createDonor();
        $payment = Payment::factory()->for($donor, 'sponsor')->succeeded()->create([
            'amount' => 17.50,
        ]);
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 22.00,
            'external_payment_id' => 'GUEST-CSV-1',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export', [
                'status' => Payment::STATUS_SUCCEEDED,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('external_payment_id', $csv);
        $this->assertStringContainsString((string) $payment->id, $csv);
        $this->assertStringContainsString((string) $guestPayment->id, $csv);
        $this->assertStringContainsString('متبرع زائر', $csv);
        $this->assertStringContainsString('GUEST-CSV-1', $csv);
        $this->assertStringContainsString(Payment::STATUS_SUCCEEDED, $csv);

        $activity = Activity::query()
            ->where('description', 'finance.payments_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('payments_csv', $activity->properties->get('export_type'));
        $this->assertSame(Payment::STATUS_SUCCEEDED, $activity->properties->get('filters')['status'] ?? null);
    }

    #[Test]
    public function export_pdf_downloads_and_writes_audit_entry(): void
    {
        $donor = $this->createDonor();
        Payment::factory()->for($donor, 'sponsor')->succeeded()->create([
            'amount' => 70.00,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
        ]);
        $guestPayment = Payment::factory()->succeeded()->create([
            'sponsor_id' => null,
            'is_guest' => true,
            'amount' => 44.00,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.payments.export-pdf', [
                'gateway' => Payment::GATEWAY_MYFATOORAH,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $renderedPdfView = view('admin.finances.exports.payments-pdf', [
            'payments' => collect([$guestPayment->load('sponsor')]),
            'generated_at' => now(),
        ])->render();
        $this->assertStringContainsString('متبرع زائر', $renderedPdfView);

        $activity = Activity::query()
            ->where('description', 'finance.payments_exported')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame('payments_pdf', $activity->properties->get('export_type'));
        $this->assertSame(Payment::GATEWAY_MYFATOORAH, $activity->properties->get('filters')['gateway'] ?? null);
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
