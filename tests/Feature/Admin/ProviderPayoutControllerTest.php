<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\ProviderPayout;
use App\Models\ProviderPayoutItem;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class ProviderPayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $provider;

    private Ewallet $providerWallet;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'phone_number' => '966500000111',
        ]);
        $this->provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966500000111',
            'email' => $this->provider->email,
            'business_name_ar' => 'Provider Shop',
            'business_name_en' => 'Provider Shop',
            'unified_number' => '7000000011',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);

        $this->providerWallet = $this->provider->fresh()->providerProfile->ewallet;
    }

    #[Test]
    public function index_applies_filters_and_returns_matching_payouts(): void
    {
        $target = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $this->providerWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-13 10:00:00',
            'amount' => 120.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
        ]);

        $otherProvider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $otherProvider->assignRole('provider');
        ProviderProfile::create([
            'user_id' => $otherProvider->id,
            'full_name_ar' => 'Other',
            'full_name_en' => 'Other',
            'phone_number' => '966500000222',
            'email' => $otherProvider->email,
            'business_name_ar' => 'Other Shop',
            'business_name_en' => 'Other Shop',
            'unified_number' => '7000000012',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);
        $otherWallet = $otherProvider->fresh()->providerProfile->ewallet;

        ProviderPayout::create([
            'provider_id' => $otherProvider->id,
            'provider_wallet_id' => $otherWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-20 10:00:00',
            'amount' => 90.00,
            'status' => ProviderPayout::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.provider-payouts.index', [
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'provider_id' => $this->provider->id,
            'date_from' => '2026-04-10',
            'date_to' => '2026-04-15',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.finances.provider-payouts.index');
        $response->assertViewHas('payouts', function ($payouts) use ($target): bool {
            $ids = collect($payouts->items())->pluck('id')->all();

            return $ids === [$target->id];
        });
    }

    #[Test]
    public function show_loads_payout_relations_for_details_page(): void
    {
        $payout = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $this->providerWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-13 10:00:00',
            'amount' => 120.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'confirmed_by' => $this->admin->id,
            'rejected_by' => $this->admin->id,
            'cancelled_by' => $this->admin->id,
        ]);

        $fund = FundTransaction::create([
            'wallet_id' => $this->providerWallet->id,
            'sponsor_id' => null,
            'source' => FundTransaction::SOURCE_PAYOUT,
            'amount' => 120.00,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => null,
            'request_id' => null,
            'order_redemption_id' => null,
            'provider_payout_id' => $payout->id,
        ]);
        ProviderPayoutItem::create([
            'provider_payout_id' => $payout->id,
            'fund_transaction_id' => $fund->id,
            'amount' => 120.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.finances.provider-payouts.show', $payout));

        $response->assertOk();
        $response->assertViewIs('admin.finances.provider-payouts.show');
        $response->assertViewHas('payout', fn (ProviderPayout $viewPayout): bool => $viewPayout->is($payout));
    }

    #[Test]
    public function receipt_file_returns_404_when_receipt_is_missing_or_not_on_disk(): void
    {
        $noReceipt = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $this->providerWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-13 10:00:00',
            'amount' => 120.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'receipt_path' => null,
        ]);
        $this->actingAs($this->admin)
            ->get(route('admin.finances.provider-payouts.receipt.file', $noReceipt))
            ->assertNotFound();

        $missingFile = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $this->providerWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-13 10:00:00',
            'amount' => 120.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'receipt_path' => 'provider-payout-receipts/missing.pdf',
        ]);
        $this->actingAs($this->admin)
            ->get(route('admin.finances.provider-payouts.receipt.file', $missingFile))
            ->assertNotFound();
    }

    #[Test]
    public function receipt_file_streams_existing_local_receipt(): void
    {
        $path = 'provider-payout-receipts/test/receipt.pdf';
        Storage::disk('local')->put($path, 'receipt content');

        $payout = ProviderPayout::create([
            'provider_id' => $this->provider->id,
            'provider_wallet_id' => $this->providerWallet->id,
            'week_start_at' => '2026-04-06 00:00:00',
            'week_end_at' => '2026-04-12 23:59:59',
            'scheduled_at' => '2026-04-13 10:00:00',
            'amount' => 120.00,
            'status' => ProviderPayout::STATUS_PENDING_ADMIN_REVIEW,
            'receipt_path' => $path,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.finances.provider-payouts.receipt.file', $payout));

        $response->assertOk();
        $response->assertHeader('content-type');
    }
}
