<?php

namespace App\Http\Services;

use App\Models\Ewallet;
use App\Support\FinancialMath;
use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderPayoutLedgerService
{
    /**
     * Earning IN lines from city-fund → provider transfer (see SystemWalletService::transferToProviderForRequest).
     *
     * @return Builder<FundTransaction>
     */
    public function eligibleEarningInQuery(Ewallet $providerWallet): Builder
    {
        return FundTransaction::query()
            ->where('wallet_id', $providerWallet->id)
            ->where('direction', FundTransaction::DIRECTION_IN)
            ->where('source', FundTransaction::SOURCE_PAYOUT)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('provider_payout_items as ppi')
                    ->join('provider_payouts as pp', 'pp.id', '=', 'ppi.provider_payout_id')
                    ->whereColumn('ppi.fund_transaction_id', 'fund_transactions.id')
                    ->whereIn('pp.status', ProviderPayout::RESERVING_STATUSES);
            })
            ->orderBy('fund_transactions.id');
    }

    /**
     * @return Collection<int, FundTransaction>
     */
    public function eligibleEarningTransactions(Ewallet $providerWallet): Collection
    {
        return $this->eligibleEarningInQuery($providerWallet)->get();
    }

    public function sumEligibleAmount(Ewallet $providerWallet): string
    {
        $rows = $this->eligibleEarningTransactions($providerWallet);

        return $this->sumDecimalAmounts($rows->pluck('amount')->all());
    }

    /**
     * @param  array<int|string|float>  $amounts
     */
    public function sumDecimalAmounts(array $amounts): string
    {
        $sum = '0.00';
        foreach ($amounts as $a) {
            $sum = $this->addDecimal($sum, (string) $a);
        }

        return $this->roundDecimal($sum);
    }

    public function addDecimal(string $a, string $b): string
    {
        return FinancialMath::add($a, $b);
    }

    public function compare(string $a, string $b): int
    {
        return FinancialMath::compare($a, $b);
    }

    public function roundDecimal(string $value): string
    {
        return FinancialMath::normalize($value);
    }
}
