<?php

namespace Tests\Unit\Http\Queries\Admin;

use App\Http\Queries\Admin\AdminPaymentIndexQuery;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPaymentIndexQueryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function problem_group_status_filter_includes_failed_pending_and_processing_only(): void
    {
        $donor = User::factory()->create();

        $failed = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'INV-FAILED',
            'status' => Payment::STATUS_FAILED,
            'amount' => 10,
        ]);
        $pending = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'INV-PENDING',
            'status' => Payment::STATUS_PENDING,
            'amount' => 20,
        ]);
        $processing = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'INV-PROCESSING',
            'status' => Payment::STATUS_PROCESSING,
            'amount' => 30,
        ]);
        $succeeded = Payment::create([
            'sponsor_id' => $donor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'INV-SUCCEEDED',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 40,
        ]);

        $request = Request::create('/admin/finances/payments', 'GET', [
            'status' => 'PROBLEM_GROUP',
        ]);

        $ids = (new AdminPaymentIndexQuery)->buildQuery($request)->pluck('id')->all();

        $this->assertContains($failed->id, $ids);
        $this->assertContains($pending->id, $ids);
        $this->assertContains($processing->id, $ids);
        $this->assertNotContains($succeeded->id, $ids);
    }

    #[Test]
    public function search_filter_supports_external_id_donor_fields_and_numeric_id(): void
    {
        $targetDonor = User::factory()->create([
            'name' => 'Searchable Donor',
            'email' => 'searchable@example.com',
            'phone_number' => '966500001111',
        ]);
        $otherDonor = User::factory()->create([
            'name' => 'Other Donor',
            'email' => 'other@example.com',
            'phone_number' => '966500009999',
        ]);

        $target = Payment::create([
            'sponsor_id' => $targetDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'ABC-LOOKUP-001',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
        ]);
        Payment::create([
            'sponsor_id' => $otherDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'XYZ-OTHER-002',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 120,
        ]);

        $byExternal = Request::create('/admin/finances/payments', 'GET', ['search' => 'LOOKUP']);
        $byDonorName = Request::create('/admin/finances/payments', 'GET', ['search' => 'Searchable']);
        $byNumericId = Request::create('/admin/finances/payments', 'GET', ['search' => (string) $target->id]);

        $query = new AdminPaymentIndexQuery;
        $this->assertSame([$target->id], $query->buildQuery($byExternal)->pluck('id')->all());
        $this->assertSame([$target->id], $query->buildQuery($byDonorName)->pluck('id')->all());
        $this->assertSame([$target->id], $query->buildQuery($byNumericId)->pluck('id')->all());
    }

    #[Test]
    public function donor_gateway_date_and_amount_filters_are_applied_together(): void
    {
        $targetDonor = User::factory()->create();
        $otherDonor = User::factory()->create();

        $included = Payment::create([
            'sponsor_id' => $targetDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'INCLUDED',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 150,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        Payment::create([
            'sponsor_id' => $targetDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'LOW-AMOUNT',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
        Payment::create([
            'sponsor_id' => $otherDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'OTHER-DONOR',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 150,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
        Payment::create([
            'sponsor_id' => $targetDonor->id,
            'gateway' => 'ANOTHER_GATEWAY',
            'external_payment_id' => 'OTHER-GATEWAY',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 150,
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);
        $outOfDate = Payment::create([
            'sponsor_id' => $targetDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'OUT-OF-DATE',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 150,
        ]);
        $outOfDate->created_at = now()->subDays(10);
        $outOfDate->updated_at = now()->subDays(10);
        $outOfDate->save();

        $request = Request::create('/admin/finances/payments', 'GET', [
            'donor_id' => $targetDonor->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'min_amount' => 100,
            'max_amount' => 200,
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $ids = (new AdminPaymentIndexQuery)->buildQuery($request)->pluck('id')->all();

        $this->assertSame([$included->id], $ids);
    }
}
