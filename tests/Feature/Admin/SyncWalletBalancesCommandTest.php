<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncWalletBalancesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sync_wallet_balances_rebuilds_balance_from_ledger_transactions(): void
    {
        $wallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 40.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        FundTransaction::create([
            'wallet_id' => $wallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
            'amount' => 15.00,
            'direction' => FundTransaction::DIRECTION_OUT,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
        ]);

        $this->assertSame('25.00', (string) $wallet->fresh()->balance);

        $wallet->update(['balance' => 999.00]);

        $this->artisan('wallets:sync-balances')
            ->assertSuccessful();

        $this->assertSame('25.00', (string) $wallet->fresh()->balance);
    }
}
