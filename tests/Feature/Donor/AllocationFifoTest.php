<?php

namespace Tests\Feature\Donor;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\RequestPaymentLink;
use App\Models\User;
use App\Http\Services\AllocationService;
use App\Http\Services\AuditService;
use App\Http\Services\SystemWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllocationFifoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $donor1 = User::factory()->create();
        $donor2 = User::factory()->create();

        $p1 = Payment::create([
            'sponsor_id' => $donor1->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 50,
        ]);
        $p2 = Payment::create([
            'sponsor_id' => $donor2->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 80,
        ]);

        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donor1->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 50,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $p1->id,
        ]);
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donor2->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 80,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $p2->id,
        ]);
        $systemWallet->syncBalance();
    }

    #[Test]
    public function allocation_consumes_payments_fifo(): void
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');
        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود اختبار',
            'full_name_en' => 'Test Provider',
            'phone_number' => '966501234567',
            'email' => $provider->email,
            'business_name_ar' => 'مطعم اختبار',
            'business_name_en' => 'Test Restaurant',
            'unified_number' => '7000123456',
            'business_category' => ['restaurant'],
            'address_ar' => 'الرياض',
            'address_en' => 'Riyadh',
            'city' => 'Riyadh',
            'region' => 'central',
        ]);

        $recipient = User::factory()->create();
        $recipient->assignRole('recipient');

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 100,
            'status' => 'REQUESTED',
        ]);

        $allocationService = app(AllocationService::class);
        $allocationService->allocateToRequest($request->id, 100);

        $links = RequestPaymentLink::where('request_id', $request->id)->orderBy('payment_id')->get();
        $this->assertCount(2, $links);

        $amounts = $links->pluck('amount')->map(fn ($a) => (float) $a)->toArray();
        $this->assertEqualsWithDelta(50, $amounts[0], 0.01);
        $this->assertEqualsWithDelta(50, $amounts[1], 0.01);
    }
}
