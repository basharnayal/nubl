<?php

namespace Tests\Feature\Auth;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResubmitApplicationTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, array{open: string, close: string, closed: bool}>
     */
    private function operatingHoursPayload(): array
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = ['closed' => false, 'open' => '09:00', 'close' => '17:00'];
        }

        return $hours;
    }

    private function createRejectedRecipient(): User
    {
        $user = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'rejection_reason' => 'Bad',
        ]);
        $user->assignRole('recipient');

        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/old.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => 'recipient_address_photos/old.png',
        ]);

        return $user;
    }

    private function createRejectedProvider(): User
    {
        $user = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'name' => 'Provider EN',
            'rejection_reason' => 'Bad docs',
        ]);
        $user->assignRole('provider');

        ProviderProfile::create([
            'user_id' => $user->id,
            'full_name_ar' => 'اسم',
            'full_name_en' => 'Provider EN',
            'phone_number' => '966501112233',
            'email' => $user->email,
            'business_name_ar' => 'تجريبي',
            'business_name_en' => 'Test Co',
            'unified_number' => '1234567890',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
        ]);

        $weekdays = array_keys(config('provider.weekdays'));
        $operatingHours = [];
        foreach ($weekdays as $day) {
            $operatingHours[$day] = $day === 'friday'
                ? ['closed' => true]
                : ['open' => '09:00', 'close' => '18:00', 'closed' => false];
        }

        ProviderOperatingInfo::create([
            'user_id' => $user->id,
            'operating_hours' => $operatingHours,
            'daily_capacity' => 50,
            'service_type' => ['dine_in'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
        ]);

        ProviderFinancialInfo::create([
            'user_id' => $user->id,
            'bank_name' => 'Test Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Test Holder',
        ]);

        ProviderDocuments::create([
            'user_id' => $user->id,
            'business_license_path' => 'provider_documents/lic.pdf',
            'id_or_iqama_path' => 'provider_documents/id.pdf',
        ]);

        return $user;
    }

    #[Test]
    public function rejected_recipient_can_view_resubmit_edit_page(): void
    {
        $user = $this->createRejectedRecipient();

        $response = $this->actingAs($user)->get(route('application.resubmit.edit'));

        $response->assertOk();
        $response->assertViewIs('auth.resubmit-recipient');
    }

    #[Test]
    public function rejected_provider_can_view_resubmit_edit_page(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedProvider();
        Storage::disk('local')->put('provider_documents/lic.pdf', 'x');
        Storage::disk('local')->put('provider_documents/id.pdf', 'y');

        $response = $this->actingAs($user)->get(route('application.resubmit.edit'));

        $response->assertOk();
        $response->assertViewIs('auth.resubmit-provider');
    }

    #[Test]
    public function pending_user_is_redirected_from_resubmit_edit(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $user->assignRole('recipient');

        $response = $this->actingAs($user)->get(route('application.resubmit.edit'));

        $response->assertRedirect(route('approval.pending'));
    }

    #[Test]
    public function active_user_is_redirected_from_resubmit_edit_to_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $user->assignRole('recipient');

        $response = $this->actingAs($user)->get(route('application.resubmit.edit'));

        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function rejected_recipient_can_download_own_id_photo(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedRecipient();
        Storage::disk('local')->put('recipient_id_photos/old.png', 'fake image');

        $response = $this->actingAs($user)->get(route('application.my-file', ['type' => 'id_photo']));

        $response->assertOk();
    }

    #[Test]
    public function serve_file_returns_404_when_file_missing_on_disk(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedRecipient();

        $response = $this->actingAs($user)->get(route('application.my-file', ['type' => 'id_photo']));

        $response->assertNotFound();
    }

    #[Test]
    public function serve_file_returns_404_for_unknown_document_type(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedProvider();
        Storage::disk('local')->put('provider_documents/lic.pdf', 'x');

        $response = $this->actingAs($user)->get(route('application.my-file', ['type' => 'unknown']));

        $response->assertNotFound();
    }

    #[Test]
    public function resubmit_recipient_writes_audit_activity(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedRecipient();
        Storage::disk('local')->put('recipient_id_photos/old.png', 'x');
        Storage::disk('local')->put('recipient_address_photos/old.png', 'y');

        $this->actingAs($user)->post(route('application.resubmit.update'), [
            'name' => $user->name,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr updated',
            'id_type' => 'national_id',
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => '0',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'address_confirmation_base64' => self::VALID_BASE64_IMAGE,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'application.resubmitted',
            'causer_id' => $user->id,
        ]);
    }

    #[Test]
    public function resubmit_recipient_fails_when_kyc_missing(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $user->assignRole('recipient');
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/old.png',
        ]);

        $response = $this->actingAs($user)->post(route('application.resubmit.update'), [
            'name' => $user->name,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => '0',
        ]);

        $response->assertRedirect(route('approval.pending'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function rejected_provider_can_resubmit_without_new_document_uploads(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedProvider();
        Storage::disk('local')->put('provider_documents/lic.pdf', 'lic');
        Storage::disk('local')->put('provider_documents/id.pdf', 'id');

        $payload = [
            'full_name_ar' => 'مقدم',
            'full_name_en' => 'Provider EN',
            'business_name_ar' => 'تجريبي',
            'business_name_en' => 'Test Co',
            'unified_number' => '1234567890',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'medina',
            'region' => 'western',
            'location' => null,
            'operating_hours' => $this->operatingHoursPayload(),
            'daily_capacity' => 50,
            'service_type' => ['meal_preparation', 'delivery'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'bank_name' => 'Test Bank',
            'iban' => 'SA0380000000608010167519',
            'account_holder_name' => 'Test Holder',
        ];

        $response = $this->actingAs($user)->post(route('application.resubmit.update'), $payload);

        $response->assertRedirect(route('approval.pending'));
        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertSame(User::STATUS_PENDING_APPROVAL, $user->status);
        $this->assertNull($user->rejection_reason);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'application.resubmitted',
            'causer_id' => $user->id,
        ]);
    }

    #[Test]
    public function resubmit_recipient_updates_status_to_pending_approval(): void
    {
        Storage::fake('local');
        $user = $this->createRejectedRecipient();
        Storage::disk('local')->put('recipient_id_photos/old.png', 'x');
        Storage::disk('local')->put('recipient_address_photos/old.png', 'y');

        $this->actingAs($user)->post(route('application.resubmit.update'), [
            'name' => $user->name,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'New address',
            'id_type' => 'national_id',
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => '0',
        ]);

        $user->refresh();
        $this->assertSame(User::STATUS_PENDING_APPROVAL, $user->status);
        $this->assertNull($user->rejection_reason);
    }
}
