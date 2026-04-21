<?php

namespace Tests\Unit\Models;

use App\Models\MenuItemCategory;
use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\Ewallet;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\RecipientProfile;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function menu_item_category_casts_is_active_and_resolves_related_menu_items(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $category = MenuItemCategory::create([
            'business_category' => 'Restaurant',
            'name' => 'Burgers',
            'slug' => 'burgers',
            'is_active' => 1,
        ]);

        $item = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Classic Burger',
            'price' => 20.00,
            'category' => 'Burgers',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->assertTrue($category->fresh()->is_active);
        $this->assertTrue($category->providerMenuItems->contains($item));
    }

    #[Test]
    public function order_proof_casts_fields_and_belongs_to_order_redemption(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Rice Meal',
            'price' => 15.00,
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 15.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ]);
        $request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 15.00,
        ]);

        $redemption = OrderRedemption::create([
            'request_id' => $request->id,
            'provider_id' => $provider->id,
            'token_code' => 'abc123',
            'redeem_expires_at' => now()->addHour(),
            'status' => 'PENDING',
        ]);

        $proof = OrderProof::create([
            'order_redemption_id' => $redemption->id,
            'proof_url' => 'proofs/demo.jpg',
            'is_provider_donation' => true,
            'fulfilled_at' => now(),
        ]);

        $this->assertTrue($proof->is_provider_donation);
        $this->assertNotNull($proof->fulfilled_at);
        $this->assertSame($redemption->id, $proof->orderRedemption->id);
    }

    #[Test]
    public function provider_documents_and_financial_info_belong_to_user(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $documents = ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'docs/license.pdf',
            'id_or_iqama_path' => 'docs/id.pdf',
        ]);

        $financial = ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Demo Bank',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Provider Name',
        ]);

        $this->assertSame($provider->id, $documents->user->id);
        $this->assertSame($provider->id, $financial->user->id);
    }

    #[Test]
    public function recipient_profile_has_user_relation_and_expected_id_types_constant(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $profile = RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi',
            'short_address' => 'Riyadh',
            'id_type' => 'national_id',
            'id_photo_path' => 'ids/recipient.jpg',
        ]);

        $this->assertSame($recipient->id, $profile->user->id);
        $this->assertSame(['national_id', 'iqama'], RecipientProfile::ID_TYPES);
    }

    #[Test]
    public function provider_profile_accessor_encodes_logo_url_and_boot_creates_wallet(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $profile = ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966500111333',
            'email' => $provider->email,
            'business_name_ar' => 'Shop',
            'business_name_en' => 'Shop',
            'unified_number' => '7000000033',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'logo_path' => 'provider-logos/logo file.png',
        ]);

        if ($profile->ewallet === null) {
            Ewallet::create([
                'owner_type' => 'PROVIDER',
                'owner_id' => $profile->id,
                'balance' => 0,
                'status' => true,
            ]);
        }

        $this->assertNotNull($profile->fresh()->ewallet);
        $this->assertStringContainsString('storage/provider-logos/logo%20file.png', (string) $profile->logo_url);

        $profile->update(['logo_path' => null]);
        $this->assertNull($profile->fresh()->logo_url);
    }
}
