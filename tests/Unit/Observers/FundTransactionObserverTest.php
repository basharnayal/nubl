<?php

namespace Tests\Unit\Observers;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\User;
use App\Observers\FundTransactionObserver;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FundTransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function created_in_transaction_increments_wallet_and_logs_audit(): void
    {
        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);
        $sponsor = User::factory()->create();

        $transaction = FundTransaction::withoutEvents(function () use ($wallet, $sponsor) {
            return FundTransaction::create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => $sponsor->id,
                'source' => FundTransaction::SOURCE_DONATION,
                'amount' => 25.00,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);
        });

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')
            ->once()
            ->with(
                'fund_transaction',
                'created',
                Mockery::on(fn (array $data): bool => ($data['fund_transaction_id'] ?? null) === $transaction->id
                    && ($data['direction'] ?? null) === FundTransaction::DIRECTION_IN),
                $sponsor->id
            );

        (new FundTransactionObserver($audit))->created($transaction->fresh());

        $this->assertSame('25.00', (string) $wallet->fresh()->balance);
    }

    #[Test]
    public function created_out_transaction_decrements_wallet_balance(): void
    {
        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 50,
            'status' => true,
        ]);

        $transaction = FundTransaction::withoutEvents(function () use ($wallet) {
            return FundTransaction::create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
                'amount' => 10.00,
                'direction' => FundTransaction::DIRECTION_OUT,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);
        });

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('log')->once();

        (new FundTransactionObserver($audit))->created($transaction->fresh());

        $this->assertSame('40.00', (string) $wallet->fresh()->balance);
    }

    #[Test]
    public function created_zero_amount_transaction_is_ignored(): void
    {
        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 50,
            'status' => true,
        ]);

        $transaction = FundTransaction::withoutEvents(function () use ($wallet) {
            return FundTransaction::create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_DONATION,
                'amount' => 0,
                'direction' => FundTransaction::DIRECTION_IN,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => null,
            ]);
        });

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldNotReceive('log');

        (new FundTransactionObserver($audit))->created($transaction->fresh());

        $this->assertSame('50.00', (string) $wallet->fresh()->balance);
    }
}
