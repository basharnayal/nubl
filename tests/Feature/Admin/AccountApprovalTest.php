<?php

namespace Tests\Feature\Admin;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Http\Controllers\Admin\AccountApprovalController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function admin_can_view_pending_users(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $response = $this->actingAs($this->admin)->get(route('admin.users.pending'));

        $response->assertOk();
        $response->assertViewIs('admin.users.pending');
        $response->assertSee($provider->name);
    }

    #[Test]
    public function admin_can_approve_pending_user(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);

        $response = $this->actingAs($this->admin)->post(route('admin.users.approve', $provider));

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('success');
        $provider->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $provider->status);
        $this->assertNull($provider->rejection_reason);

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'account_approval.approved')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($provider->id, $activity->properties->get('user_id'));
        $this->assertNotNull($activity->created_at);
    }

    #[Test]
    public function admin_can_reject_pending_user(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->post(route('admin.users.reject', $recipient), [
            'rejection_reason' => 'Incomplete documents',
        ]);

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('success');
        $recipient->refresh();
        $this->assertSame(User::STATUS_REJECTED, $recipient->status);
        $this->assertSame('Incomplete documents', $recipient->rejection_reason);

        $activity = Activity::where('causer_id', $this->admin->id)
            ->where('description', 'account_approval.rejected')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($recipient->id, $activity->properties->get('user_id'));
        $this->assertSame('Incomplete documents', $activity->properties->get('rejection_reason'));
    }

    #[Test]
    public function admin_can_approve_previously_rejected_user(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'rejection_reason' => 'Old reason',
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);

        $response = $this->actingAs($this->admin)->post(route('admin.users.approve', $provider));

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('success');
        $provider->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $provider->status);
        $this->assertNull($provider->rejection_reason);
    }

    #[Test]
    public function admin_approving_recipient_sends_account_status_notification_audit(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->post(route('admin.users.approve', $recipient));

        $response->assertRedirect(route('admin.users.pending'));
        $this->assertSame(User::STATUS_ACTIVE, $recipient->fresh()->status);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'notification.sent',
            'causer_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function admin_cannot_approve_when_invalid_status(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $response = $this->actingAs($this->admin)->post(route('admin.users.approve', $provider));

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('error');
        $provider->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $provider->status);
    }

    #[Test]
    public function show_reject_form(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->get(route('admin.users.reject.form', $recipient));

        $response->assertOk();
        $response->assertViewIs('admin.users.reject-form');
        $response->assertSee($recipient->name);
    }

    #[Test]
    public function admin_cannot_show_reject_form_when_not_pending(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->get(route('admin.users.reject.form', $recipient));

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function reject_requires_reason(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->post(route('admin.users.reject', $recipient), [
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
        $recipient->refresh();
        $this->assertSame(User::STATUS_PENDING_APPROVAL, $recipient->status);
    }

    #[Test]
    public function admin_cannot_reject_when_not_pending(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($this->admin)->post(route('admin.users.reject', $recipient), [
            'rejection_reason' => 'Some reason',
        ]);

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('error');
        $recipient->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $recipient->status);
    }

    #[Test]
    public function show_provider_application(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider, [
            'business_name_en' => 'Test Restaurant',
            'business_name_ar' => 'مطعم تجريبي',
        ]);
        $weekdays = array_keys(config('provider.weekdays') ?: ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        $operatingHours = [];
        foreach ($weekdays as $day) {
            $operatingHours[$day] = $day === 'friday' ? ['closed' => true] : ['open' => '09:00', 'close' => '18:00', 'closed' => false];
        }
        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => $operatingHours,
            'daily_capacity' => 50,
            'service_type' => ['dine_in'],
            'estimated_preparation_order_time' => '30 minutes',
        ]);
        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Test Bank',
            'iban' => 'SA0000000000000000000000',
            'account_holder_name' => 'Test Holder',
        ]);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/placeholder.pdf',
            'id_or_iqama_path' => 'provider_documents/placeholder.png',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.application', $provider));

        $response->assertOk();
        $response->assertViewIs('admin.users.provider-application');
        $response->assertSee('Test Restaurant');
    }

    #[Test]
    public function show_recipient_application(): void
    {
        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');
        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'SA',
            'short_address' => 'Riyadh',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_docs/test-id.png',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.application', $recipient));

        $response->assertOk();
        $response->assertViewIs('admin.users.recipient-application');
        $response->assertSee($recipient->name);
    }

    #[Test]
    public function show_application_no_application_found(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $user->assignRole('provider');
        // No ProviderProfile

        $response = $this->actingAs($this->admin)->get(route('admin.users.application', $user));

        $response->assertRedirect(route('admin.users.pending'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function serve_file_returns_404_for_missing_file(): void
    {
        Storage::fake('local');

        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/nonexistent.pdf',
            'id_or_iqama_path' => 'provider_documents/nonexistent.png',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.file', [$provider, 'business_license']));

        $response->assertStatus(404);
    }

    #[Test]
    public function serve_file_returns_file_when_exists(): void
    {
        Storage::fake('local');
        $path = 'provider_documents/test-license.pdf';
        Storage::disk('local')->put($path, 'fake pdf content');

        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => $path,
            'id_or_iqama_path' => 'provider_documents/nonexistent.png',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.file', [$provider, 'business_license']));

        $response->assertOk();
        $response->assertHeader('content-type');
    }

    #[Test]
    public function serve_file_returns_provider_id_document_when_exists(): void
    {
        Storage::fake('local');
        $path = 'provider_documents/test-id.png';
        Storage::disk('local')->put($path, 'fake id content');

        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/license.pdf',
            'id_or_iqama_path' => $path,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.file', [$provider, 'id_or_iqama']))
            ->assertOk();
    }

    #[Test]
    public function serve_file_returns_recipient_documents_when_exists(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('recipient_docs/id.png', 'id');
        Storage::disk('local')->put('recipient_docs/address.png', 'address');

        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');
        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'SA',
            'short_address' => 'Riyadh',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_docs/id.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $recipient->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => 'recipient_docs/address.png',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.file', [$recipient, 'id_photo']))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.users.file', [$recipient, 'address_confirmation']))
            ->assertOk();
    }

    #[Test]
    public function serve_file_returns_404_for_invalid_type(): void
    {
        Storage::fake('local');

        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');
        $this->createProviderProfile($provider);
        ProviderDocuments::create([
            'user_id' => $provider->id,
            'business_license_path' => 'provider_documents/placeholder.pdf',
            'id_or_iqama_path' => 'provider_documents/placeholder.png',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.file', [$provider, 'invalid_type']));

        $response->assertStatus(404);
    }

    #[Test]
    public function direct_application_methods_redirect_when_expected_profile_is_missing(): void
    {
        $controller = app(AccountApprovalController::class);

        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $recipient = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $this->assertTrue($controller->showProviderApplication($provider)->isRedirect(route('admin.users.pending')));
        $this->assertTrue($controller->showRecipientApplication($recipient)->isRedirect(route('admin.users.pending')));
    }

    protected function createProviderProfile(User $user, array $overrides = []): ProviderProfile
    {
        return ProviderProfile::create(array_merge([
            'user_id' => $user->id,
            'full_name_ar' => 'اسم تجريبي',
            'full_name_en' => 'Test Name',
            'phone_number' => '966501234567',
            'email' => $user->email,
            'business_name_ar' => 'تجريبي',
            'business_name_en' => 'Test',
            'unified_number' => '1234567890',
            'business_category' => ['restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'Central',
        ], $overrides));
    }

    #[Test]
    public function non_admin_cannot_access_pending_users(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('donor');

        $response = $this->actingAs($donor)->get(route('admin.users.pending'));

        $response->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_pending_users(): void
    {
        $response = $this->get(route('admin.users.pending'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_cannot_approve_user(): void
    {
        $provider = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $response = $this->actingAs($recipient)->post(route('admin.users.approve', $provider));

        $response->assertForbidden();
        $provider->refresh();
        // Status must be unchanged
        $this->assertSame(User::STATUS_PENDING_APPROVAL, $provider->status);
    }
}
