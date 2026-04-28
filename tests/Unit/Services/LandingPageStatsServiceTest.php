<?php

namespace Tests\Unit\Services;

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\LandingPageStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LandingPageStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    }

    #[Test]
    public function service_returns_safe_fallback_structure_when_no_live_data_exists(): void
    {
        $stats = app(LandingPageStatsService::class)->getHeroStats();

        $this->assertSame(0, $stats['totalDelivered']);
        $this->assertSame(0, $stats['familiesSupported']);
        $this->assertSame(0, $stats['localProviders']);
        $this->assertCount(5, $stats['feedItems']);
        $this->assertFalse($stats['trustLedger']['is_live']);
        $this->assertSame(0, $stats['trustLedger']['shown']);
        $this->assertSame(0, $stats['trustLedger']['total']);
        $this->assertNull($stats['trustBadges']);
        $this->assertSame(
            ['grocery' => 0, 'catering' => 0, 'bakery' => 0, 'restaurant' => 0],
            $stats['providerCounts']
        );
    }

    #[Test]
    public function service_aggregates_live_metrics_from_requests_transactions_and_provider_categories(): void
    {
        $recipientOne = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipientTwo = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $providerGrocery = $this->createVisibleProvider(['grocery'], 'medina');
        $providerCatering = $this->createVisibleProvider(['catering'], 'medina');
        $providerBakery = $this->createVisibleProvider(['bakery'], 'medina');
        $providerRestaurant = $this->createVisibleProvider(['restaurant'], 'medina');

        $hiddenProvider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => false,
            'accepting_orders' => true,
        ]);
        $hiddenProvider->assignRole('provider');
        $this->createProviderProfile($hiddenProvider, ['grocery'], 'medina');

        RequestModel::create([
            'recipient_id' => $recipientOne->id,
            'provider_id' => $providerGrocery->id,
            'reserved_amount' => 120,
            'status' => 'REDEEMABLE',
            'updated_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(10),
        ]);
        RequestModel::create([
            'recipient_id' => $recipientTwo->id,
            'provider_id' => $providerBakery->id,
            'reserved_amount' => 80,
            'status' => 'FULFILLED',
            'updated_at' => now()->subHours(2),
            'created_at' => now()->subHours(4),
        ]);
        RequestModel::create([
            'recipient_id' => $recipientTwo->id,
            'provider_id' => $providerCatering->id,
            'reserved_amount' => 50,
            'status' => 'REQUESTED',
            'updated_at' => now()->subMinutes(2),
            'created_at' => now()->subMinutes(3),
        ]);

        $systemWallet = Ewallet::create([
            'owner_type' => 'SYSTEM',
            'owner_id' => null,
            'balance' => 0,
            'status' => true,
        ]);

        $donorOne = User::factory()->create();
        $donorTwo = User::factory()->create();
        $paymentOne = Payment::create([
            'sponsor_id' => $donorOne->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'LP-001',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 300,
        ]);
        $paymentTwo = Payment::create([
            'sponsor_id' => $donorTwo->id,
            'gateway' => Payment::GATEWAY_MYFATOORAH,
            'external_payment_id' => 'LP-002',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 100,
        ]);

        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donorOne->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 300,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentOne->id,
        ]);
        FundTransaction::create([
            'wallet_id' => $systemWallet->id,
            'sponsor_id' => $donorTwo->id,
            'source' => FundTransaction::SOURCE_DONATION,
            'amount' => 100,
            'direction' => FundTransaction::DIRECTION_IN,
            'payment_id' => $paymentTwo->id,
        ]);

        $stats = app(LandingPageStatsService::class)->getHeroStats();

        $this->assertSame(200, $stats['totalDelivered']);
        $this->assertSame(2, $stats['familiesSupported']);
        $this->assertSame(4, $stats['localProviders']);

        $this->assertTrue($stats['trustLedger']['is_live']);
        $this->assertSame(2, $stats['trustLedger']['shown']);
        $this->assertSame(2, $stats['trustLedger']['total']);
        $this->assertCount(2, $stats['feedItems']);

        $this->assertNotNull($stats['trustBadges']);
        $this->assertSame(50.0, $stats['trustBadges']['delivered']);
        $this->assertSame(50.0, $stats['trustBadges']['held']);

        $this->assertSame(
            ['grocery' => 1, 'catering' => 1, 'bakery' => 1, 'restaurant' => 1],
            $stats['providerCounts']
        );

        $this->assertStringContainsString('medina', strtolower($stats['feedItems'][0]['row1']));
    }

    private function createVisibleProvider(array $categories, string $city): User
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');

        $this->createProviderProfile($provider, $categories, $city);

        return $provider;
    }

    private function createProviderProfile(User $provider, array $categories, string $city): ProviderProfile
    {
        return ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '966511111111',
            'email' => $provider->email,
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'unified_number' => '7000000001',
            'business_category' => $categories,
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => $city,
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
        ]);
    }
}
