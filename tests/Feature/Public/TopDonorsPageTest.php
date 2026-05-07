<?php

namespace Tests\Feature\Public;

use App\Models\Payment;
use App\Models\User;
use App\Services\TopDonorsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TopDonorsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the donor role exists so factories can assign it
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        Cache::flush();
    }

    // ── Routing ────────────────────────────────────────────────────────

    #[Test]
    public function top_donors_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('top-donors.index'));

        $response->assertStatus(200);
        $response->assertViewIs('top-donors.index');
    }

    #[Test]
    public function top_donors_page_is_accessible_without_authentication(): void
    {
        $response = $this->get('/top-donors');

        $response->assertStatus(200);
    }

    // ── Empty state ────────────────────────────────────────────────────

    #[Test]
    public function empty_state_shown_when_no_donors(): void
    {
        $response = $this->get(route('top-donors.index'));

        $response->assertViewHas('donors', []);
    }

    // ── Named donors ───────────────────────────────────────────────────

    #[Test]
    public function named_donor_with_succeeded_payment_appears_in_list(): void
    {
        $donor = User::factory()->create(['name' => 'أحمد السلمي', 'membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 500.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);

        $this->assertCount(1, array_filter($donors, fn ($d) => $d['name'] === 'أحمد السلمي'));
    }

    #[Test]
    public function same_donor_multiple_payments_appear_once_with_summed_total(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->count(3)->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 100.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);
        $named  = array_filter($donors, fn ($d) => ! $d['is_anonymous']);

        $this->assertCount(1, $named);
        $this->assertEquals(300.00, array_values($named)[0]['total']);
    }

    // ── Anonymous donors ───────────────────────────────────────────────

    #[Test]
    public function anonymous_registered_donor_appears_as_faeel_khayr(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 200.00,
            'is_guest'     => false,
            'is_anonymous' => true,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);
        $anon   = array_filter($donors, fn ($d) => $d['is_anonymous']);

        $this->assertNotEmpty($anon);
        $this->assertEquals(200.00, array_values($anon)[0]['total']);
    }

    #[Test]
    public function guest_payment_counts_in_anonymous_pool(): void
    {
        Payment::factory()->create([
            'sponsor_id'   => null,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 150.00,
            'is_guest'     => true,
            'is_anonymous' => true,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);
        $anon   = array_filter($donors, fn ($d) => $d['is_anonymous']);

        $this->assertNotEmpty($anon);
        $this->assertEquals(150.00, array_values($anon)[0]['total']);
    }

    #[Test]
    public function anonymous_and_guest_pools_are_combined_into_one_entry(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 100.00,
            'is_guest'     => false,
            'is_anonymous' => true,
        ]);
        Payment::factory()->create([
            'sponsor_id'   => null,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 50.00,
            'is_guest'     => true,
            'is_anonymous' => true,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);
        $anon   = array_values(array_filter($donors, fn ($d) => $d['is_anonymous']));

        $this->assertCount(1, $anon);
        $this->assertEquals(150.00, $anon[0]['total']);
    }

    // ── Non-succeeded payments excluded ────────────────────────────────

    #[Test]
    public function failed_payment_is_excluded_from_list(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->failed()->create([
            'sponsor_id'   => $donor->id,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);

        $this->assertEmpty(array_filter($donors, fn ($d) => ! $d['is_anonymous']));
    }

    #[Test]
    public function pending_payment_is_excluded_from_list(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->pending()->create([
            'sponsor_id'   => $donor->id,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $donors = (new TopDonorsService)->getTopDonors(100);

        $this->assertEmpty(array_filter($donors, fn ($d) => ! $d['is_anonymous']));
    }

    // ── Ordering ───────────────────────────────────────────────────────

    #[Test]
    public function donors_are_ordered_by_total_descending(): void
    {
        $big   = User::factory()->create(['name' => 'المتبرع الكبير', 'membership_type' => User::MEMBERSHIP_DONOR]);
        $small = User::factory()->create(['name' => 'المتبرع الصغير', 'membership_type' => User::MEMBERSHIP_DONOR]);

        Payment::factory()->create(['sponsor_id' => $big->id,   'status' => Payment::STATUS_SUCCEEDED, 'amount' => 1000, 'is_guest' => false, 'is_anonymous' => false]);
        Payment::factory()->create(['sponsor_id' => $small->id, 'status' => Payment::STATUS_SUCCEEDED, 'amount' => 100,  'is_guest' => false, 'is_anonymous' => false]);

        $donors = (new TopDonorsService)->getTopDonors(100);
        $named  = array_values(array_filter($donors, fn ($d) => ! $d['is_anonymous']));

        $this->assertEquals('المتبرع الكبير', $named[0]['name']);
        $this->assertEquals(1, $named[0]['rank']);
        $this->assertEquals('المتبرع الصغير', $named[1]['name']);
    }

    // ── Cache ──────────────────────────────────────────────────────────

    #[Test]
    public function top_donors_list_is_cached(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 300.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $service = new TopDonorsService;
        $first   = $service->getTopDonors(100);

        // Add a second payment — without clearing cache, result should remain the same
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 300.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $second = $service->getTopDonors(100);

        $named = array_values(array_filter($first, fn ($d) => ! $d['is_anonymous']));
        $this->assertEquals(300.00, $named[0]['total']); // cached, not 600
        $this->assertEquals($first, $second);
    }

    #[Test]
    public function clearing_cache_returns_fresh_data(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR]);
        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 300.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        $service = new TopDonorsService;
        $service->getTopDonors(100); // warm cache

        Payment::factory()->create([
            'sponsor_id'   => $donor->id,
            'status'       => Payment::STATUS_SUCCEEDED,
            'amount'       => 300.00,
            'is_guest'     => false,
            'is_anonymous' => false,
        ]);

        TopDonorsService::clearCache();
        $fresh = $service->getTopDonors(100);
        $named = array_values(array_filter($fresh, fn ($d) => ! $d['is_anonymous']));

        $this->assertEquals(600.00, $named[0]['total']);
    }

    // ── is_anonymous checkbox stored correctly ─────────────────────────

    #[Test]
    public function donation_form_stores_is_anonymous_false_by_default(): void
    {
        $donor = User::factory()->create(['membership_type' => User::MEMBERSHIP_DONOR, 'status' => User::STATUS_ACTIVE]);
        $donor->assignRole('donor');

        $this->actingAs($donor)
            ->post(route('donor.payments.initiate'), [
                'amount' => 50,
                // is_anonymous not sent → defaults false
            ]);

        $payment = Payment::where('sponsor_id', $donor->id)->latest()->first();

        $this->assertNotNull($payment);
        $this->assertFalse($payment->is_anonymous);
    }
}
