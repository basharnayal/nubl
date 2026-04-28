<?php

namespace Tests\Feature\Donor;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DonationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $donor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->donor = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->donor->assignRole('donor');
    }

    #[Test]
    public function donor_index_lists_only_own_successful_donations(): void
    {
        Payment::factory()->for($this->donor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 31.00,
            'external_payment_id' => 'OWN-SUCCESS',
        ]);
        Payment::factory()->for($this->donor, 'sponsor')->create([
            'status' => Payment::STATUS_FAILED,
            'amount' => 66.00,
            'external_payment_id' => 'OWN-FAILED',
        ]);
        $otherDonor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $otherDonor->assignRole('donor');
        Payment::factory()->for($otherDonor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 77.00,
            'external_payment_id' => 'OTHER-SUCCESS',
        ]);

        $response = $this->actingAs($this->donor)->get(route('donor.donations.index'));

        $response->assertOk();
        $response->assertViewIs('donor.donations.index');
        $response->assertSee('31.00', false);
        $response->assertDontSee('66.00', false);
        $response->assertDontSee('77.00', false);
    }

    #[Test]
    public function donor_can_open_new_donation_page(): void
    {
        $response = $this->actingAs($this->donor)->get(route('donor.donations.new'));

        $response->assertOk();
        $response->assertViewIs('donor.donations.new');
        $response->assertSee(__('Proceed to Payment'), false);
    }

    #[Test]
    public function donor_can_view_receipt_for_own_successful_payment_only(): void
    {
        $ownSucceeded = Payment::factory()->for($this->donor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 49.90,
        ]);
        $ownFailed = Payment::factory()->for($this->donor, 'sponsor')->create([
            'status' => Payment::STATUS_FAILED,
            'amount' => 10.00,
        ]);

        $otherDonor = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $otherDonor->assignRole('donor');
        $otherPayment = Payment::factory()->for($otherDonor, 'sponsor')->create([
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 70.00,
        ]);

        $this->actingAs($this->donor)
            ->get(route('donor.donations.receipt', $ownSucceeded))
            ->assertOk()
            ->assertViewIs('donor.donations.receipt')
            ->assertSee((string) $ownSucceeded->id, false);

        $this->actingAs($this->donor)
            ->get(route('donor.donations.receipt', $otherPayment))
            ->assertForbidden();

        $this->actingAs($this->donor)
            ->get(route('donor.donations.receipt', $ownFailed))
            ->assertNotFound();
    }
}
