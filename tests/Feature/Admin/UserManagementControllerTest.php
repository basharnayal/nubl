<?php

namespace Tests\Feature\Admin;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (['admin', 'donor', 'recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function admin_can_open_create_and_edit_forms_with_roles_from_guard_scope(): void
    {
        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole('donor');

        $createResponse = $this->actingAs($this->admin)->get(route('admin.manage.users.create'));
        $createResponse->assertOk();
        $createResponse->assertViewIs('admin.manage.users.create');
        $createResponse->assertViewHas('allRoles', function ($roles): bool {
            $names = $roles->pluck('name')->all();
            $sorted = $names;
            sort($sorted);

            return $names === $sorted && in_array('admin', $names, true) && in_array('donor', $names, true);
        });

        $editResponse = $this->actingAs($this->admin)->get(route('admin.manage.users.edit', $user));
        $editResponse->assertOk();
        $editResponse->assertViewIs('admin.manage.users.edit');
        $editResponse->assertViewHas('allRoles', function ($roles): bool {
            $names = $roles->pluck('name')->all();
            $sorted = $names;
            sort($sorted);

            return $names === $sorted && in_array('provider', $names, true);
        });
    }

    #[Test]
    public function admin_can_create_recipient_with_profile_and_kyc_files(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.manage.users.store'), [
            'name' => 'Recipient User',
            'email' => 'recipient.coverage@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'roles' => ['recipient'],
            'phone_number' => '0501234567',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Coverage Street',
            'id_type' => RecipientProfile::ID_TYPES[0],
            'id_photo' => UploadedFile::fake()->image('id-photo.jpg'),
            'income_band' => RecipientKycDetails::INCOME_BANDS[0],
            'household_size' => 4,
            'marital_status' => RecipientKycDetails::MARITAL_STATUSES[0],
            'is_student' => true,
            'id_number' => '1234567890',
            'employment_status' => RecipientKycDetails::EMPLOYMENT_STATUSES[0],
        ]);

        $response->assertRedirect(route('admin.manage.users.index'));
        $response->assertSessionHas('success');

        $recipient = User::query()->where('email', 'recipient.coverage@example.com')->firstOrFail();
        $this->assertTrue($recipient->hasRole('recipient'));

        $profile = RecipientProfile::query()->where('user_id', $recipient->id)->firstOrFail();
        $kyc = RecipientKycDetails::query()->where('user_id', $recipient->id)->firstOrFail();

        $this->assertSame('Saudi Arabia', $profile->nationality);
        $this->assertSame('1234567890', $profile->id_number);
        $this->assertNotNull($profile->id_photo_path);
        $this->assertSame(RecipientKycDetails::EMPLOYMENT_STATUSES[0], $kyc->employment_status);

        Storage::disk('local')->assertExists($profile->id_photo_path);
    }

    #[Test]
    public function admin_can_create_provider_with_operating_financial_and_document_records(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.manage.users.store'), [
            'name' => 'Provider User',
            'email' => 'provider.coverage@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'roles' => ['provider'],
            'phone_number' => '0502234567',
            'full_name_ar' => 'مزود تغطية',
            'full_name_en' => 'Coverage Provider',
            'business_name_ar' => 'متجر تغطية',
            'business_name_en' => 'Coverage Store',
            'unified_number' => '7000099999',
            'business_category' => ['restaurant'],
            'address_ar' => 'العنوان',
            'address_en' => 'Address',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => '24.7136,46.6753',
            'daily_capacity' => 120,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '30 mins',
            'adoption_support' => array_key_first(config('provider.adoption_support_options')),
            'bank_name' => 'Coverage Bank',
            'iban' => 'SA4450000000000000000001',
            'account_holder_name' => 'Coverage Holder',
            'business_license' => UploadedFile::fake()->create('license.pdf', 50, 'application/pdf'),
            'id_or_iqama' => UploadedFile::fake()->create('id.pdf', 50, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.manage.users.index'));
        $response->assertSessionHas('success');

        $provider = User::query()->where('email', 'provider.coverage@example.com')->firstOrFail();
        $this->assertTrue($provider->hasRole('provider'));

        $profile = ProviderProfile::query()->where('user_id', $provider->id)->firstOrFail();
        $operating = ProviderOperatingInfo::query()->where('user_id', $provider->id)->firstOrFail();
        $financial = ProviderFinancialInfo::query()->where('user_id', $provider->id)->firstOrFail();
        $docs = ProviderDocuments::query()->where('user_id', $provider->id)->firstOrFail();

        $this->assertSame('Coverage Store', $profile->business_name_en);
        $this->assertSame(120, $operating->daily_capacity);
        $this->assertSame('Coverage Bank', $financial->bank_name);
        $this->assertNotNull($docs->business_license_path);
        $this->assertNotNull($docs->id_or_iqama_path);
        Storage::disk('local')->assertExists($docs->business_license_path);
        Storage::disk('local')->assertExists($docs->id_or_iqama_path);
    }

    #[Test]
    public function admin_can_update_recipient_and_replace_existing_files(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_number' => '966501111111',
        ]);
        $recipient->assignRole('recipient');

        $profile = RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Old Address',
            'id_type' => RecipientProfile::ID_TYPES[0],
            'id_photo_path' => 'recipient_id_photos/old-id.jpg',
        ]);
        $kyc = RecipientKycDetails::create([
            'user_id' => $recipient->id,
            'income_band' => RecipientKycDetails::INCOME_BANDS[0],
            'household_size' => 2,
            'marital_status' => RecipientKycDetails::MARITAL_STATUSES[0],
            'is_student' => false,
        ]);
        Storage::disk('local')->put($profile->id_photo_path, 'old-id');

        $response = $this->actingAs($this->admin)->put(route('admin.manage.users.update', $recipient), [
            'name' => 'Recipient Updated',
            'email' => $recipient->email,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'roles' => ['recipient'],
            'phone_number' => '0503334444',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'New Address',
            'id_type' => RecipientProfile::ID_TYPES[1],
            'income_band' => RecipientKycDetails::INCOME_BANDS[1],
            'household_size' => 5,
            'marital_status' => RecipientKycDetails::MARITAL_STATUSES[1],
            'is_student' => true,
            'id_number' => '9876543210',
            'id_photo' => UploadedFile::fake()->image('new-id.jpg'),
        ]);

        $response->assertRedirect(route('admin.manage.users.show', $recipient));
        $response->assertSessionHas('success');

        $recipient->refresh();
        $this->assertSame('Recipient Updated', $recipient->name);
        $this->assertSame('966503334444', $recipient->phone_number);

        $newProfile = $recipient->recipientProfile()->firstOrFail();

        $this->assertNotSame('recipient_id_photos/old-id.jpg', $newProfile->id_photo_path);
        $this->assertSame('9876543210', $newProfile->id_number);
        Storage::disk('local')->assertMissing('recipient_id_photos/old-id.jpg');
        Storage::disk('local')->assertExists($newProfile->id_photo_path);
    }

    #[Test]
    public function admin_update_recipient_creates_kyc_record_when_missing(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_number' => '966504444444',
        ]);
        $recipient->assignRole('recipient');

        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Address',
            'id_type' => RecipientProfile::ID_TYPES[0],
            'id_photo_path' => 'recipient_id_photos/current-id.jpg',
        ]);
        Storage::disk('local')->put('recipient_id_photos/current-id.jpg', 'current');

        $this->assertNull($recipient->fresh()->recipientKycDetails);

        $response = $this->actingAs($this->admin)->put(route('admin.manage.users.update', $recipient), [
            'name' => 'Recipient KYC Created',
            'email' => $recipient->email,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'roles' => ['recipient'],
            'phone_number' => '0505556666',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Address Updated',
            'id_type' => RecipientProfile::ID_TYPES[0],
            'id_number' => '1111111111',
            'income_band' => RecipientKycDetails::INCOME_BANDS[2],
            'household_size' => 3,
            'marital_status' => RecipientKycDetails::MARITAL_STATUSES[2],
            'is_student' => false,
        ]);

        $response->assertRedirect(route('admin.manage.users.show', $recipient));
        $response->assertSessionHas('success');

        $recipient->refresh();
        $this->assertNotNull($recipient->recipientKycDetails);
        $this->assertSame(3, $recipient->recipientKycDetails->household_size);
    }

    #[Test]
    public function admin_can_update_provider_and_replace_documents_and_operating_hours(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_number' => '966506666666',
        ]);
        $provider->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966506666666',
            'email' => $provider->email,
            'business_name_ar' => 'متجر',
            'business_name_en' => 'Store',
            'unified_number' => '7000011111',
            'business_category' => ['restaurant'],
            'address_ar' => 'old',
            'address_en' => 'old',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
        ]);
        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => $this->closedHoursPayload(),
            'daily_capacity' => 10,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '20 mins',
            'adoption_support' => array_key_first(config('provider.adoption_support_options')),
        ]);
        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Old Bank',
            'iban' => 'SA0000000000000000000000',
            'account_holder_name' => 'Old Holder',
        ]);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/old-license.pdf',
            'id_or_iqama_path' => 'provider_documents/old-id.pdf',
        ]);
        Storage::disk('local')->put('provider_documents/old-license.pdf', 'old-license');
        Storage::disk('local')->put('provider_documents/old-id.pdf', 'old-id');

        $response = $this->actingAs($this->admin)->put(route('admin.manage.users.update', $provider), [
            'name' => 'Provider Updated',
            'email' => $provider->email,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'roles' => ['provider'],
            'phone_number' => '0507778888',
            'full_name_ar' => 'مزود محدث',
            'full_name_en' => 'Updated Provider',
            'business_name_ar' => 'متجر محدث',
            'business_name_en' => 'Updated Store',
            'unified_number' => '7000088888',
            'business_category' => ['restaurant'],
            'address_ar' => 'new ar',
            'address_en' => 'new en',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => '24.7,46.6',
            'daily_capacity' => 99,
            'service_type' => ['delivery'],
            'estimated_preparation_order_time' => '45 mins',
            'adoption_support' => array_key_first(config('provider.adoption_support_options')),
            'bank_name' => 'Updated Bank',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Updated Holder',
            'operating_hours' => $this->openHoursPayload(),
            'business_license' => UploadedFile::fake()->create('new-license.pdf', 50, 'application/pdf'),
            'id_or_iqama' => UploadedFile::fake()->create('new-id.pdf', 50, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.manage.users.show', $provider));
        $response->assertSessionHas('success');

        $provider->refresh();
        $this->assertSame('Provider Updated', $provider->name);
        $this->assertSame('966507778888', $provider->phone_number);
        $this->assertSame(99, $provider->providerOperatingInfo->daily_capacity);
        $this->assertSame('Updated Bank', $provider->providerFinancialInfo->bank_name);

        $docs = $provider->providerDocuments;
        $this->assertNotNull($docs);
        $this->assertNotSame('provider_documents/old-license.pdf', $docs->business_license_path);
        $this->assertNotSame('provider_documents/old-id.pdf', $docs->id_or_iqama_path);
        Storage::disk('local')->assertMissing('provider_documents/old-license.pdf');
        Storage::disk('local')->assertMissing('provider_documents/old-id.pdf');
        Storage::disk('local')->assertExists($docs->business_license_path);
        Storage::disk('local')->assertExists($docs->id_or_iqama_path);
    }

    /**
     * @return array<string, array{closed: bool}>
     */
    private function closedHoursPayload(): array
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = ['closed' => true];
        }

        return $hours;
    }

    /**
     * @return array<string, array{open?: string, close?: string, closed: bool}>
     */
    private function openHoursPayload(): array
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = [
                'open' => '09:00',
                'close' => '17:00',
                'closed' => false,
            ];
        }

        return $hours;
    }
}

