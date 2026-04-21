<?php

namespace Tests\Feature\Profile;

use App\Models\ProviderFinancialInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
    }

    #[Test]
    public function edit_loads_provider_profile_and_financial_data_for_provider_users(): void
    {
        $provider = $this->createProviderUser();
        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Bank A',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Provider Holder',
        ]);

        $response = $this->actingAs($provider)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewHas('providerProfile', fn (ProviderProfile $value): bool => $value->user_id === $provider->id);
        $response->assertViewHas('providerFinancial', fn (ProviderFinancialInfo $value): bool => $value->user_id === $provider->id);
        $response->assertViewHas('recipientProfile', null);
    }

    #[Test]
    public function edit_loads_recipient_profile_for_recipient_users(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi',
            'short_address' => 'Street',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/id.png',
        ]);

        $response = $this->actingAs($recipient)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewHas('providerProfile', null);
        $response->assertViewHas('providerFinancial', null);
        $response->assertViewHas('recipientProfile', fn (RecipientProfile $value): bool => $value->user_id === $recipient->id);
    }

    #[Test]
    public function upload_photo_replaces_and_removes_existing_provider_logo(): void
    {
        $provider = $this->createProviderUser();
        $profile = $provider->providerProfile;

        Storage::disk('public')->put('provider-logos/old-logo.jpg', 'old-logo');
        $profile->update(['logo_path' => 'provider-logos/old-logo.jpg']);

        $this->actingAs($provider)
            ->post(route('profile.photo.upload'), [
                'profile_logo' => UploadedFile::fake()->image('new-logo.jpg'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-updated');

        $profile->refresh();
        $this->assertNotNull($profile->logo_path);
        $this->assertNotSame('provider-logos/old-logo.jpg', $profile->logo_path);
        Storage::disk('public')->assertMissing('provider-logos/old-logo.jpg');
        Storage::disk('public')->assertExists($profile->logo_path);

        $this->actingAs($provider)
            ->post(route('profile.photo.upload'), [
                'remove_profile_logo' => true,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-updated');

        $profile->refresh();
        $this->assertNull($profile->logo_path);
    }

    #[Test]
    public function upload_photo_is_a_no_op_for_non_provider_users(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $this->actingAs($recipient)
            ->post(route('profile.photo.upload'), [
                'profile_logo' => UploadedFile::fake()->image('ignored-logo.jpg'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-updated');

        $this->assertSame([], Storage::disk('public')->allFiles('provider-logos'));
    }

    #[Test]
    public function update_syncs_provider_profile_email_and_writes_provider_audit(): void
    {
        $provider = $this->createProviderUser();

        $response = $this->actingAs($provider)->patch(route('profile.update'), [
            'name' => 'Updated Provider',
            'email' => 'updated-provider@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', [
            'id' => $provider->id,
            'name' => 'Updated Provider',
            'email' => 'updated-provider@example.com',
        ]);
        $this->assertDatabaseHas('provider_profiles', [
            'user_id' => $provider->id,
            'email' => 'updated-provider@example.com',
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'provider_account.updated',
            'causer_id' => $provider->id,
        ]);
    }

    #[Test]
    public function update_writes_recipient_audit_when_recipient_profile_changes(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $this->actingAs($recipient)->patch(route('profile.update'), [
            'name' => 'Updated Recipient',
            'email' => 'updated-recipient@example.com',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'recipient_account.updated',
            'causer_id' => $recipient->id,
        ]);
    }

    private function createProviderUser(): User
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'phone_number' => '966500111222',
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider',
            'full_name_en' => 'Provider',
            'phone_number' => '966500111222',
            'email' => $provider->email,
            'business_name_ar' => 'Provider Shop',
            'business_name_en' => 'Provider Shop',
            'unified_number' => '7000000022',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'central',
            'location' => null,
        ]);

        return $provider;
    }
}
