<?php

namespace Tests\Feature\Donor;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentCallbackControllerPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
    }

    #[Test]
    public function success_page_shows_receipt_for_the_payment_owner_when_status_is_succeeded(): void
    {
        $donor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor->assignRole('donor');

        $payment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'ext-1',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 125.50,
            'notes' => ['source' => 'test'],
        ]);

        $response = $this->actingAs($donor)->get(route('donor.payments.success', [
            'payment_id' => $payment->id,
        ]));

        $response->assertOk();
        $response->assertSee('Donation Receipt');
        $response->assertSee('DON-'.str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT));
        $response->assertSee('125.50');
    }

    #[Test]
    public function success_page_returns_forbidden_for_non_owner_payment(): void
    {
        $owner = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $owner->assignRole('donor');

        $otherDonor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $otherDonor->assignRole('donor');

        $payment = Payment::create([
            'sponsor_id' => $owner->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'ext-2',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 99.00,
        ]);

        $this->actingAs($otherDonor)
            ->get(route('donor.payments.success', ['payment_id' => $payment->id]))
            ->assertForbidden();
    }

    #[Test]
    public function failed_page_handles_owned_or_missing_payment_id_and_blocks_other_user_payment(): void
    {
        $donor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $donor->assignRole('donor');

        $otherDonor = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $otherDonor->assignRole('donor');

        $ownFailedPayment = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'ext-3',
            'status' => Payment::STATUS_FAILED,
            'amount' => 30.00,
        ]);

        $otherPayment = Payment::create([
            'sponsor_id' => $otherDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'ext-4',
            'status' => Payment::STATUS_FAILED,
            'amount' => 45.00,
        ]);

        $this->actingAs($donor)
            ->get(route('donor.payments.failed', ['payment_id' => $ownFailedPayment->id]))
            ->assertOk()
            ->assertSee('Payment was not completed');

        $this->actingAs($donor)
            ->get(route('donor.payments.failed'))
            ->assertOk()
            ->assertSee('Payment was not completed');

        $this->actingAs($donor)
            ->get(route('donor.payments.failed', ['payment_id' => $otherPayment->id]))
            ->assertForbidden();
    }
}
