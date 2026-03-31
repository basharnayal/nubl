<?php

namespace Tests\Feature\Notifications;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Notifications\AccountApprovalPendingNotification;
use App\Notifications\DocumentsResubmittedForReviewNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAccountNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createAdmin(): User
    {
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        return $admin;
    }

    #[Test]
    public function recipient_registration_notifies_admins_with_account_approval_pending(): void
    {
        $admin = $this->createAdmin();

        $this->post('/register', [
            'membership_type' => 'recipient',
            'name' => 'Test Recipient',
            'phone_number' => '0509876543',
            'email' => 'recipient@example.com',
            'password' => 'password',
            'nationality' => 'Saudi Arabia',
            'short_address' => '123 Test Street, Riyadh',
            'id_type' => 'national_id',
            'id_photo_base64' => self::VALID_BASE64_IMAGE,
            'income_band' => '1000-1500',
            'household_size' => 4,
            'marital_status' => 'married',
            'is_student' => false,
            'address_confirmation_base64' => self::VALID_BASE64_IMAGE,
        ]);

        $applicant = User::where('email', 'recipient@example.com')->first();
        $this->assertNotNull($applicant);

        $notification = $admin->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('account_approval_pending', $notification->data['type']);
        $this->assertSame($applicant->id, $notification->data['user_id']);
        $this->assertSame('recipient', $notification->data['membership_type']);
        $this->assertArrayHasKey('happened_at', $notification->data);
        $this->assertStringContainsString('/admin/users/'.$applicant->id.'/application', $notification->data['url']);
    }

    #[Test]
    public function donor_registration_notifies_admins_with_new_user_registered(): void
    {
        $admin = $this->createAdmin();

        $this->post('/register', [
            'membership_type' => 'donor',
            'name' => 'Test Donor',
            'phone_number' => '0501234567',
            'email' => 'donor@example.com',
            'password' => 'password',
        ]);

        $notification = $admin->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('new_user_registered', $notification->data['type']);
    }

    #[Test]
    public function rejected_to_pending_triggers_documents_resubmitted_for_review(): void
    {
        $admin = $this->createAdmin();

        $recipient = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'rejection_reason' => 'Bad scan',
        ]);
        $recipient->assignRole('recipient');

        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/old.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $recipient->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => 'recipient_address_photos/old.png',
        ]);

        $recipient->update([
            'status' => User::STATUS_PENDING_APPROVAL,
            'rejection_reason' => null,
        ]);

        $notification = $admin->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('documents_resubmitted_for_review', $notification->data['type']);
        $this->assertSame($recipient->id, $notification->data['user_id']);
        $this->assertArrayHasKey('happened_at', $notification->data);
    }

    #[Test]
    public function notification_controller_formats_account_approval_pending_for_admin(): void
    {
        $admin = $this->createAdmin();
        $applicant = User::factory()->create([
            'name' => 'Jane Applicant',
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $applicant->assignRole('recipient');

        $admin->notify(new AccountApprovalPendingNotification($applicant));

        $response = $this->actingAs($admin)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('notifications.0.type', 'account_approval_pending');
        $response->assertJsonPath('notifications.0.icon', 'warning');
        $response->assertJsonPath('notifications.0.icon_svg', 'clock');
        $this->assertNotEmpty($response->json('notifications.0.title'));
        $this->assertNotEmpty($response->json('notifications.0.url'));
        $this->assertNotNull($response->json('notifications.0.created_at'));
    }

    #[Test]
    public function notification_controller_formats_documents_resubmitted_for_admin(): void
    {
        $admin = $this->createAdmin();
        $applicant = User::factory()->create([
            'name' => 'John Resubmit',
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $applicant->assignRole('provider');

        $admin->notify(new DocumentsResubmittedForReviewNotification($applicant));

        $response = $this->actingAs($admin)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('notifications.0.type', 'documents_resubmitted_for_review');
        $response->assertJsonPath('notifications.0.icon', 'info');
        $this->assertStringContainsString('/admin/users/'.$applicant->id.'/application', $response->json('notifications.0.url'));
    }

    #[Test]
    public function recipient_can_resubmit_documents_after_rejection(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();

        $recipient = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'rejection_reason' => 'Blurry',
        ]);
        $recipient->assignRole('recipient');

        Storage::disk('local')->put('recipient_id_photos/old.png', 'x');
        Storage::disk('local')->put('recipient_address_photos/old.png', 'y');

        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/old.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $recipient->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => 'recipient_address_photos/old.png',
        ]);

        $beforeCount = $admin->notifications()->count();

        $response = $this->actingAs($recipient)->post(route('application.resubmit.update'), [
            'name' => $recipient->name,
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

        $response->assertRedirect(route('approval.pending'));
        $recipient->refresh();
        $this->assertSame(User::STATUS_PENDING_APPROVAL, $recipient->status);
        $this->assertNull($recipient->rejection_reason);

        $this->assertSame($beforeCount + 1, $admin->fresh()->notifications()->count());
        $latest = $admin->fresh()->notifications()->latest()->first();
        $this->assertSame('documents_resubmitted_for_review', $latest->data['type']);
    }
}
