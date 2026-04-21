<?php

namespace Tests\Unit\Services;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ResubmitApplicationService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResubmitApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function resolve_document_path_returns_null_when_file_missing(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/missing.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertNull($service->resolveDocumentPath($user->fresh(), 'id_photo'));
    }

    #[Test]
    public function resolve_document_path_returns_path_when_file_exists(): void
    {
        Storage::fake('local');
        $path = 'recipient_id_photos/x.png';
        Storage::disk('local')->put($path, 'data');
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => $path,
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertSame($path, $service->resolveDocumentPath($user->fresh(), 'id_photo'));
    }

    #[Test]
    public function resolve_document_path_returns_provider_and_recipient_secondary_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('provider_documents/lic.pdf', 'license');
        Storage::disk('local')->put('provider_documents/id.pdf', 'id');
        Storage::disk('local')->put('recipient_address_photos/address.png', 'address');

        $provider = $this->createProviderApplicant();
        $recipient = $this->createRecipientApplicant();

        $service = app(ResubmitApplicationService::class);

        $this->assertSame('provider_documents/lic.pdf', $service->resolveDocumentPath($provider->fresh(), 'business_license'));
        $this->assertSame('provider_documents/id.pdf', $service->resolveDocumentPath($provider->fresh(), 'id_or_iqama'));
        $this->assertSame('recipient_address_photos/address.png', $service->resolveDocumentPath($recipient->fresh(), 'address_confirmation'));
    }

    #[Test]
    public function resubmit_recipient_returns_false_when_kyc_missing(): void
    {
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/x.png',
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertFalse($service->resubmitRecipient($user->fresh(), [
            'name' => 'Test',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => '0',
        ], null, null));
    }

    #[Test]
    public function resubmit_provider_returns_false_when_related_records_are_missing(): void
    {
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_PROVIDER]);

        $service = app(ResubmitApplicationService::class);

        $this->assertFalse($service->resubmitProvider($user, [], [], null, null));
    }

    #[Test]
    public function resubmit_provider_updates_password_and_replaces_uploaded_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('provider_documents/lic.pdf', 'old license');
        Storage::disk('local')->put('provider_documents/id.pdf', 'old id');

        $user = $this->createProviderApplicant();
        $service = app(ResubmitApplicationService::class);

        $this->assertTrue($service->resubmitProvider(
            $user,
            $this->providerPayload(['password' => 'new-password']),
            $this->operatingHoursPayload(),
            UploadedFile::fake()->create('new-license.pdf', 12, 'application/pdf'),
            UploadedFile::fake()->create('new-id.pdf', 12, 'application/pdf')
        ));

        $user->refresh();
        $docs = $user->providerDocuments;

        $this->assertSame(User::STATUS_PENDING_APPROVAL, $user->status);
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNotSame('provider_documents/lic.pdf', $docs->business_license_path);
        $this->assertNotSame('provider_documents/id.pdf', $docs->id_or_iqama_path);
        Storage::disk('local')->assertMissing('provider_documents/lic.pdf');
        Storage::disk('local')->assertMissing('provider_documents/id.pdf');
        Storage::disk('local')->assertExists($docs->business_license_path);
        Storage::disk('local')->assertExists($docs->id_or_iqama_path);
    }

    #[Test]
    public function resubmit_recipient_cleans_new_uploads_when_transaction_fails(): void
    {
        Storage::fake('local');
        $user = $this->createRecipientApplicant();
        $service = app(ResubmitApplicationService::class);

        try {
            $service->resubmitRecipient($user, [
                'nationality' => 'Saudi Arabia',
                'short_address' => 'Addr',
                'id_type' => 'national_id',
                'income_band' => '1000-1500',
                'household_size' => 2,
                'marital_status' => 'single',
                'is_student' => '0',
            ], self::VALID_BASE64_IMAGE, self::VALID_BASE64_IMAGE);
            $this->fail('Expected resubmission to fail with an incomplete validated payload.');
        } catch (\Throwable) {
            $this->assertSame([], Storage::disk('local')->files('recipient_id_photos'));
            $this->assertSame([], Storage::disk('local')->files('recipient_address_photos'));
        }
    }

    #[Test]
    public function resubmit_provider_cleans_new_uploads_when_transaction_fails(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('provider_documents/lic.pdf', 'old license');
        Storage::disk('local')->put('provider_documents/id.pdf', 'old id');

        $user = $this->createProviderApplicant();
        $service = app(ResubmitApplicationService::class);

        try {
            $service->resubmitProvider(
                $user,
                [],
                $this->operatingHoursPayload(),
                UploadedFile::fake()->create('new-license.pdf', 12, 'application/pdf'),
                UploadedFile::fake()->create('new-id.pdf', 12, 'application/pdf')
            );
            $this->fail('Expected provider resubmission to fail with an incomplete validated payload.');
        } catch (\Throwable) {
            $this->assertEqualsCanonicalizing(
                ['provider_documents/id.pdf', 'provider_documents/lic.pdf'],
                Storage::disk('local')->files('provider_documents')
            );
        }
    }

    #[Test]
    public function prepare_recipient_edit_data_contains_expected_keys(): void
    {
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'p.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $data = app(ResubmitApplicationService::class)->prepareRecipientEditData($user->fresh());

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('kyc', $data);
        $this->assertSame($user->id, $data['user']->id);
    }

    private function createRecipientApplicant(): User
    {
        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => 'Old reason',
        ]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/id.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => 'recipient_address_photos/address.png',
        ]);

        return $user;
    }

    private function createProviderApplicant(): User
    {
        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => 'Old reason',
        ]);
        ProviderProfile::create([
            'user_id' => $user->id,
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '966501234567',
            'email' => $user->email,
            'business_name_ar' => 'Shop AR',
            'business_name_en' => 'Shop EN',
            'unified_number' => '7000123456',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => 'Riyadh',
            'region' => 'Central',
        ]);
        ProviderOperatingInfo::create([
            'user_id' => $user->id,
            'operating_hours' => $this->operatingHoursPayload(),
            'daily_capacity' => 25,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
        ]);
        ProviderFinancialInfo::create([
            'user_id' => $user->id,
            'bank_name' => 'Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Provider EN',
        ]);
        ProviderDocuments::create([
            'user_id' => $user->id,
            'business_license_path' => 'provider_documents/lic.pdf',
            'id_or_iqama_path' => 'provider_documents/id.pdf',
        ]);

        return $user;
    }

    /**
     * @return array<string, array{open?: string, close?: string, closed: bool}>
     */
    private function operatingHoursPayload(): array
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = $day === 'friday'
                ? ['closed' => true]
                : ['open' => '09:00', 'close' => '18:00', 'closed' => false];
        }

        return $hours;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function providerPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN Updated',
            'business_name_ar' => 'Shop AR',
            'business_name_en' => 'Shop EN',
            'unified_number' => '7000123456',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => 'Riyadh',
            'region' => 'Central',
            'location' => null,
            'daily_capacity' => 30,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'bank_name' => 'Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Provider EN',
        ], $overrides);
    }
}
