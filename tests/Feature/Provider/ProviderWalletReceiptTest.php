<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderPayout;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderWalletReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    }

    #[Test]
    public function provider_can_download_own_transferred_payout_receipt(): void
    {
        $provider = $this->provider();
        $payout = $this->payoutFor($provider, ProviderPayout::STATUS_TRANSFERRED, 'receipts/own.pdf');
        Storage::disk('local')->put($payout->receipt_path, 'receipt-body');

        $this->actingAs($provider)
            ->get(route('provider.wallet.payout-receipt', $payout))
            ->assertOk();
    }

    #[Test]
    public function provider_cannot_download_another_providers_receipt(): void
    {
        $owner = $this->provider();
        $other = $this->provider();
        $payout = $this->payoutFor($owner, ProviderPayout::STATUS_TRANSFERRED, 'receipts/other.pdf');
        Storage::disk('local')->put($payout->receipt_path, 'receipt-body');

        $this->actingAs($other)
            ->get(route('provider.wallet.payout-receipt', $payout))
            ->assertForbidden();
    }

    #[Test]
    public function pending_payout_receipt_is_not_downloadable_by_provider(): void
    {
        $provider = $this->provider();
        $payout = $this->payoutFor($provider, ProviderPayout::STATUS_PENDING_ADMIN_REVIEW, 'receipts/pending.pdf');
        Storage::disk('local')->put($payout->receipt_path, 'receipt-body');

        $this->actingAs($provider)
            ->get(route('provider.wallet.payout-receipt', $payout))
            ->assertNotFound();
    }

    private function provider(): User
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider',
            'phone_number' => '9665'.str_pad((string) $provider->id, 8, '0', STR_PAD_LEFT),
            'email' => $provider->email,
            'business_name_ar' => 'Shop AR',
            'business_name_en' => 'Shop',
            'unified_number' => '7000'.str_pad((string) $provider->id, 6, '0', STR_PAD_LEFT),
            'business_category' => ['restaurant'],
            'address_ar' => 'Riyadh AR',
            'address_en' => 'Riyadh',
            'city' => 'Riyadh',
            'region' => 'central',
        ]);

        return $provider;
    }

    private function payoutFor(User $provider, string $status, string $receiptPath): ProviderPayout
    {
        $wallet = $provider->providerProfile->ewallet;

        return ProviderPayout::create([
            'provider_id' => $provider->id,
            'provider_wallet_id' => $wallet->id,
            'week_start_at' => now()->subWeek()->startOfWeek(),
            'week_end_at' => now()->subWeek()->endOfWeek(),
            'scheduled_at' => now(),
            'amount' => 75.00,
            'status' => $status,
            'receipt_path' => $receiptPath,
        ]);
    }
}
