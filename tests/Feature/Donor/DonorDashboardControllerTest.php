<?php

namespace Tests\Feature\Donor;

use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DonorDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['donor', 'recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function donor_dashboard_returns_aggregated_metrics_for_the_authenticated_donor(): void
    {
        $donor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor->assignRole('donor');

        $recipientA = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipientA->assignRole('recipient');

        $recipientB = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipientB->assignRole('recipient');

        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);
        $provider->assignRole('provider');

        $paymentOne = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'DONOR-1',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);
        $paymentTwo = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'DONOR-2',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'DONOR-FAILED',
            'status' => Payment::STATUS_FAILED,
            'amount' => 999,
        ]);

        $requested = RequestModel::create([
            'recipient_id' => $recipientA->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 20,
            'status' => 'REQUESTED',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        $fulfilled = RequestModel::create([
            'recipient_id' => $recipientA->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 40,
            'status' => 'FULFILLED',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);
        RequestModel::create([
            'recipient_id' => $recipientB->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 15,
            'status' => 'APPROVED',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        RequestPaymentLink::create([
            'payment_id' => $paymentOne->id,
            'request_id' => $requested->id,
            'amount' => 60,
        ]);
        RequestPaymentLink::create([
            'payment_id' => $paymentTwo->id,
            'request_id' => $fulfilled->id,
            'amount' => 40,
        ]);

        $redemption = OrderRedemption::create([
            'request_id' => $fulfilled->id,
            'provider_id' => $provider->id,
            'token_code' => 'token-123',
            'short_code_hash' => hash('sha256', '123456'),
            'token_ciphertext' => 'ciphertext',
            'short_code_ciphertext' => 'short-ciphertext',
            'token_qr_url' => 'https://example.com/qr',
            'ttl_minutes' => 180,
            'redeem_expires_at' => now()->addHour(),
            'status' => 'REDEEMED',
        ]);
        OrderProof::create([
            'order_redemption_id' => $redemption->id,
            'proof_url' => 'proof/path.jpg',
            'is_provider_donation' => false,
            'fulfilled_at' => now()->subHours(6),
        ]);

        $response = $this->actingAs($donor)->get(route('donor.dashboard'));

        $response->assertOk();
        $response->assertViewIs('donor.dashboard');

        $this->assertSame(150.0, $response->viewData('donorTotalDonated'));
        $this->assertSame(2, $response->viewData('donorDonationCount'));
        $this->assertSame(2, $response->viewData('donorRequestsFunded'));
        $this->assertSame(1, $response->viewData('donorRequestsDelivered'));
        $this->assertSame(100.0, $response->viewData('donorAmountAllocated'));

        $transparency = $response->viewData('donorTransparency');
        $this->assertSame(2, $transparency['requests_from_your_funds']);
        $this->assertSame(1, $transparency['requests_delivered']);
        $this->assertSame(100.0, $transparency['amount_allocated']);

        $this->assertSame(2, $response->viewData('pendingRequestsCount'));
        $this->assertSame(35, (int) $response->viewData('pendingAmount'));
        $this->assertSame(2, $response->viewData('recipientsWaiting'));
        $this->assertSame(33, $response->viewData('fundedPercent'));

        $timeline = $response->viewData('donorImpactTimeline');
        $this->assertNotEmpty($timeline);
        $this->assertMatchesRegularExpression('/^R-[A-F0-9]{8}$/', $timeline[0]['pseudonymous_id']);
    }

    #[Test]
    public function donor_dashboard_does_not_count_redeemable_requests_as_delivered_before_fulfillment(): void
    {
        $donor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor->assignRole('donor');

        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);
        $provider->assignRole('provider');

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'DONOR-REDEEMABLE',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 30,
        ]);

        $redeemable = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 30,
            'status' => 'REDEEMABLE',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        RequestPaymentLink::create([
            'payment_id' => $payment->id,
            'request_id' => $redeemable->id,
            'amount' => 30,
        ]);

        $response = $this->actingAs($donor)->get(route('donor.dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('donorRequestsFunded'));
        $this->assertSame(0, $response->viewData('donorRequestsDelivered'));

        $timeline = $response->viewData('donorImpactTimeline');
        $this->assertSame('REDEEMABLE', $timeline[0]['status_key']);
        $this->assertSame(__('Ready for redemption'), $timeline[0]['status']);
    }
}
