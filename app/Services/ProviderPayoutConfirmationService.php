<?php

namespace App\Services;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use App\Models\User;
use App\Notifications\ProviderPayoutTransferredNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProviderPayoutConfirmationService
{
    public function __construct(
        private ProviderPayoutLedgerService $ledgerService,
        private AuditService $auditService
    ) {}

    public function confirm(
        ProviderPayout $payout,
        User $admin,
        ?string $referenceNumber,
        ?string $receiptPath,
        ?string $adminNote
    ): void {
        DB::transaction(function () use ($payout, $admin, $referenceNumber, $receiptPath, $adminNote) {
            /** @var ProviderPayout $payout */
            $payout = ProviderPayout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if (! $payout->isConfirmable()) {
                throw new RuntimeException(__('finance.provider_payouts.errors.not_confirmable'));
            }

            $payout->load(['items.fundTransaction', 'providerWallet']);

            $items = $payout->items;
            if ($items->isEmpty()) {
                throw new RuntimeException(__('finance.provider_payouts.errors.no_items'));
            }

            $sum = $this->ledgerService->sumDecimalAmounts($items->pluck('amount')->all());
            if ($this->ledgerService->compare($sum, $this->ledgerService->roundDecimal((string) $payout->amount)) !== 0) {
                $this->auditService->log('provider_payout', 'payout_confirm_amount_mismatch', [
                    'provider_payout_id' => $payout->id,
                    'payout_amount' => (string) $payout->amount,
                    'items_sum' => $sum,
                ], $admin->id);
                throw new RuntimeException(__('finance.provider_payouts.errors.amount_mismatch'));
            }

            $wallet = $payout->providerWallet;
            if (! $wallet) {
                throw new RuntimeException(__('finance.provider_payouts.errors.no_wallet'));
            }

            Ewallet::query()->whereKey($wallet->id)->lockForUpdate()->first();

            $balance = $this->ledgerService->roundDecimal((string) $wallet->fresh()->balance);
            if ($this->ledgerService->compare($balance, $sum) < 0) {
                $this->auditService->log('provider_payout', 'payout_confirm_insufficient_balance', [
                    'provider_payout_id' => $payout->id,
                    'balance' => $balance,
                    'required' => $sum,
                ], $admin->id);
                throw new RuntimeException(__('finance.provider_payouts.errors.insufficient_balance'));
            }

            $out = FundTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'sponsor_id' => null,
                'source' => FundTransaction::SOURCE_PROVIDER_BANK_PAYOUT,
                'amount' => $sum,
                'direction' => FundTransaction::DIRECTION_OUT,
                'payment_id' => null,
                'request_id' => null,
                'order_redemption_id' => null,
                'provider_payout_id' => $payout->id,
            ]);

            $payout->update([
                'status' => ProviderPayout::STATUS_TRANSFERRED,
                'fund_transaction_out_id' => $out->id,
                'reference_number' => $referenceNumber,
                'receipt_path' => $receiptPath ?? $payout->receipt_path,
                'admin_note' => $adminNote ?? $payout->admin_note,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
            ]);

            $this->auditService->log('provider_payout', 'payout_request_confirmed', [
                'provider_payout_id' => $payout->id,
                'fund_transaction_id' => $out->id,
                'amount' => $sum,
                'provider_id' => $payout->provider_id,
            ], $admin->id);

            $provider = $payout->provider;
            if ($provider) {
                $provider->notify(new ProviderPayoutTransferredNotification($payout->fresh()));
            }
        });
    }

    public function reject(ProviderPayout $payout, User $admin, ?string $note): void
    {
        DB::transaction(function () use ($payout, $admin, $note) {
            $payout = ProviderPayout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if (! $payout->isConfirmable()) {
                throw new RuntimeException(__('finance.provider_payouts.errors.not_rejectable'));
            }

            $payout->update([
                'status' => ProviderPayout::STATUS_REJECTED,
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
                'admin_note' => $note ?? $payout->admin_note,
            ]);

            $this->auditService->log('provider_payout', 'payout_request_rejected', [
                'provider_payout_id' => $payout->id,
                'provider_id' => $payout->provider_id,
            ], $admin->id);
        });
    }

    public function cancel(ProviderPayout $payout, User $admin, ?string $note): void
    {
        DB::transaction(function () use ($payout, $admin, $note) {
            $payout = ProviderPayout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if (! $payout->isConfirmable()) {
                throw new RuntimeException(__('finance.provider_payouts.errors.not_cancellable'));
            }

            $payout->update([
                'status' => ProviderPayout::STATUS_CANCELLED,
                'cancelled_by' => $admin->id,
                'cancelled_at' => now(),
                'admin_note' => $note ?? $payout->admin_note,
            ]);

            $this->auditService->log('provider_payout', 'payout_request_cancelled', [
                'provider_payout_id' => $payout->id,
                'provider_id' => $payout->provider_id,
            ], $admin->id);
        });
    }
}
