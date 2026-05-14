<?php

namespace Tests\Feature\Admin;

use App\Models\Ewallet;
use App\Models\Request as RequestModel;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase2PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    // =========================================================================
    // users.read
    // =========================================================================

    #[Test]
    public function admin_with_users_read_can_access_user_list_and_show(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.manage.users.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.manage.users.show', $target))->assertOk();
    }

    #[Test]
    public function admin_without_users_read_is_forbidden_from_user_list_and_show(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->revoke('users.read');

        $this->actingAs($this->admin)->get(route('admin.manage.users.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.manage.users.show', $target))->assertForbidden();
    }

    // =========================================================================
    // users.create
    // =========================================================================

    #[Test]
    public function admin_with_users_create_can_access_create_form(): void
    {
        $this->actingAs($this->admin)->get(route('admin.manage.users.create'))->assertOk();
    }

    #[Test]
    public function admin_without_users_create_is_forbidden_from_create_form(): void
    {
        $this->revoke('users.create');

        $this->actingAs($this->admin)->get(route('admin.manage.users.create'))->assertForbidden();
    }

    // =========================================================================
    // users.update
    // =========================================================================

    #[Test]
    public function admin_with_users_update_can_access_edit_form(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);

        $this->actingAs($this->admin)->get(route('admin.manage.users.edit', $target))->assertOk();
    }

    #[Test]
    public function admin_without_users_update_is_forbidden_from_edit_form(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->revoke('users.update');

        $this->actingAs($this->admin)->get(route('admin.manage.users.edit', $target))->assertForbidden();
    }

    // =========================================================================
    // users.delete
    // =========================================================================

    #[Test]
    public function admin_without_users_delete_cannot_destroy_user(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->revoke('users.delete');

        $this->actingAs($this->admin)
            ->delete(route('admin.manage.users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    // =========================================================================
    // users.deactivate / users.reactivate
    // =========================================================================

    #[Test]
    public function admin_without_users_deactivate_cannot_deactivate_user(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->revoke('users.deactivate');

        $this->actingAs($this->admin)
            ->post(route('admin.manage.users.deactivate', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    #[Test]
    public function admin_without_users_reactivate_cannot_reactivate_user(): void
    {
        $target = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => false]);
        $this->revoke('users.reactivate');

        $this->actingAs($this->admin)
            ->post(route('admin.manage.users.reactivate', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
    }

    // =========================================================================
    // users.assign.roles (FormRequest authorize)
    // =========================================================================

    #[Test]
    public function admin_without_users_assign_roles_gets_403_on_store_when_roles_field_present(): void
    {
        $this->revoke('users.assign.roles');

        $this->actingAs($this->admin)
            ->post(route('admin.manage.users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'membership_type' => 'donor',
                'phone_number' => '+966501234567',
                'roles' => ['donor'],
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // accounts.approve
    // =========================================================================

    #[Test]
    public function admin_with_accounts_approve_can_access_pending_users_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.pending'))->assertOk();
    }

    #[Test]
    public function admin_without_accounts_approve_is_forbidden_from_pending_users_and_approval_actions(): void
    {
        $pending = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $pending->assignRole('recipient');

        $this->revoke('accounts.approve');

        $this->actingAs($this->admin)->get(route('admin.users.pending'))->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('admin.users.approve', $pending))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => User::STATUS_PENDING_APPROVAL]);
    }

    // =========================================================================
    // requests.review
    // =========================================================================

    #[Test]
    public function admin_with_requests_review_can_access_request_queue(): void
    {
        Ewallet::create(['owner_type' => 'SYSTEM', 'owner_id' => null, 'balance' => 0, 'status' => true]);

        $this->actingAs($this->admin)->get(route('admin.requests.index'))->assertOk();
    }

    #[Test]
    public function admin_without_requests_review_is_forbidden_from_request_queue(): void
    {
        $this->revoke('requests.review');

        $this->actingAs($this->admin)->get(route('admin.requests.index'))->assertForbidden();
    }

    // =========================================================================
    // requests.approve / requests.reject (controller-level abort_unless)
    // =========================================================================

    #[Test]
    public function admin_without_requests_approve_cannot_approve_a_request(): void
    {
        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $provider->assignRole('provider');
        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient->assignRole('recipient');

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50.00,
            'status' => 'ADMIN_PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->revoke('requests.approve');

        $this->actingAs($this->admin)
            ->put(route('admin.requests.update', $request), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'ADMIN_PENDING']);
    }

    #[Test]
    public function admin_without_requests_reject_cannot_reject_a_request(): void
    {
        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $provider->assignRole('provider');
        $recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $recipient->assignRole('recipient');

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 50.00,
            'status' => 'ADMIN_PENDING',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->revoke('requests.reject');

        $this->actingAs($this->admin)
            ->put(route('admin.requests.update', $request), [
                'action' => 'reject',
                'rejection_reason_code' => 'Other',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('requests', ['id' => $request->id, 'status' => 'ADMIN_PENDING']);
    }

    // =========================================================================
    // allocation.pause_global
    // =========================================================================

    #[Test]
    public function admin_with_allocation_pause_global_can_pause_and_resume_globally(): void
    {
        $this->actingAs($this->admin)->post(route('admin.allocation.pause'))->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.allocation.resume'))->assertRedirect();
    }

    #[Test]
    public function admin_without_allocation_pause_global_is_forbidden_from_global_pause_and_resume(): void
    {
        $this->revoke('allocation.pause_global');

        $this->actingAs($this->admin)->post(route('admin.allocation.pause'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.allocation.resume'))->assertForbidden();
    }

    // =========================================================================
    // allocation.pause_per_provider
    // =========================================================================

    #[Test]
    public function admin_with_allocation_pause_per_provider_can_pause_and_resume_a_provider(): void
    {
        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $provider->assignRole('provider');

        $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.pause', $provider))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.resume', $provider))
            ->assertRedirect();
    }

    #[Test]
    public function admin_without_allocation_pause_per_provider_is_forbidden_from_provider_controls(): void
    {
        $provider = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $provider->assignRole('provider');

        $this->revoke('allocation.pause_per_provider');

        $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.pause', $provider))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('admin.allocation.provider.resume', $provider))
            ->assertForbidden();
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function revoke(string $permission): void
    {
        Role::findByName('admin')->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
