<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MiddlewareGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'donor', 'recipient', 'provider'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $router = app('router');
        $router->middleware(['web', 'email.verified'])
            ->get('/_test/middleware/email-verified', fn () => response('ok'));
        $router->middleware(['web', 'permission:users.manage'])
            ->get('/_test/middleware/permission-protected', fn () => response('ok'));
        $router->middleware(['web', 'role:admin'])
            ->get('/_test/middleware/role-admin-only', fn () => response('ok'));
        $router->middleware(['web', 'phone.verified'])
            ->get('/_test/middleware/phone-verified', fn () => response('ok'));
    }

    #[Test]
    public function guest_login_form_response_sets_no_store_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
    }

    #[Test]
    public function json_requests_do_not_get_no_store_headers_from_auth_form_middleware(): void
    {
        $response = $this->getJson(route('login'));

        $this->assertNotSame(
            'no-store, no-cache, must-revalidate, max-age=0',
            $response->headers->get('Cache-Control')
        );
    }

    #[Test]
    public function authenticated_html_pages_set_no_store_headers(): void
    {
        $user = $this->makeUser('donor');

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('Pragma', 'no-cache');
    }

    #[Test]
    public function pending_and_rejected_users_are_redirected_by_account_approval_middleware(): void
    {
        $pending = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'is_active' => true,
        ]);
        $rejected = User::factory()->create([
            'status' => User::STATUS_REJECTED,
            'is_active' => true,
        ]);

        $this->actingAs($pending)
            ->get(route('profile.edit'))
            ->assertRedirect(route('approval.pending'));

        $this->actingAs($rejected)
            ->get(route('profile.edit'))
            ->assertRedirect(route('approval.pending'));
    }

    #[Test]
    public function inactive_user_is_logged_out_by_account_approval_middleware(): void
    {
        $inactive = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactive)->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function pending_only_mode_redirects_active_users_and_allows_pending_users(): void
    {
        $active = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $pending = User::factory()->create([
            'status' => User::STATUS_PENDING_APPROVAL,
            'is_active' => true,
        ]);

        $this->actingAs($active)
            ->get(route('approval.pending'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($pending)
            ->get(route('approval.pending'))
            ->assertOk();
    }

    #[Test]
    public function phone_verified_middleware_redirects_unverified_users_but_allows_verification_routes(): void
    {
        config(['app.phone_verification_enabled' => true]);

        $unverified = $this->makeUser('donor', ['phone_verified_at' => null]);

        $this->actingAs($unverified)
            ->get('/_test/middleware/phone-verified')
            ->assertRedirect(route('verification.phone'));

        $this->actingAs($unverified)
            ->get(route('verification.phone'))
            ->assertOk();

        $verified = $this->makeUser('donor', ['phone_verified_at' => now()]);

        $this->actingAs($verified)
            ->get('/_test/middleware/phone-verified')
            ->assertOk();
    }

    #[Test]
    public function phone_verified_middleware_redirects_guests_to_login(): void
    {
        config(['app.phone_verification_enabled' => true]);

        $this->get('/_test/middleware/phone-verified')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function phone_verified_middleware_allows_requests_when_phone_verification_is_disabled(): void
    {
        config(['app.phone_verification_enabled' => false]);

        $guestResponse = $this->get('/_test/middleware/phone-verified');
        $guestResponse->assertOk();

        $unverified = $this->makeUser('donor', ['phone_verified_at' => null]);
        $this->actingAs($unverified)
            ->get('/_test/middleware/phone-verified')
            ->assertOk();
    }

    #[Test]
    public function email_verified_middleware_behaves_for_enabled_and_disabled_modes(): void
    {
        config(['app.email_verification_enabled' => true]);

        $unverified = User::factory()->unverified()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $verified = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $this->actingAs($unverified)
            ->get('/_test/middleware/email-verified')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($verified)
            ->get('/_test/middleware/email-verified')
            ->assertOk();

        config(['app.email_verification_enabled' => false]);

        $this->get('/_test/middleware/email-verified')->assertOk();
    }

    #[Test]
    public function permission_middleware_redirects_guests_and_blocks_users_without_permission(): void
    {
        $response = $this->get('/_test/middleware/permission-protected');
        $response->assertRedirect(route('login'));

        $user = $this->makeUser('donor');
        $this->actingAs($user)
            ->get('/_test/middleware/permission-protected')
            ->assertForbidden();

        $user->givePermissionTo('users.manage');
        $this->actingAs($user)
            ->get('/_test/middleware/permission-protected')
            ->assertOk();
    }

    #[Test]
    public function role_middleware_blocks_non_admin_and_allows_admin(): void
    {
        $this->get('/_test/middleware/role-admin-only')->assertForbidden();

        $donor = $this->makeUser('donor');
        $this->actingAs($donor)
            ->get('/_test/middleware/role-admin-only')
            ->assertForbidden();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)
            ->get('/_test/middleware/role-admin-only')
            ->assertOk();
    }

    #[Test]
    public function dashboard_redirect_by_role_sends_users_to_correct_dashboard(): void
    {
        config(['app.phone_verification_enabled' => false]);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $donor = $this->makeUser('donor');
        $this->actingAs($donor)
            ->get(route('dashboard'))
            ->assertRedirect(route('donor.dashboard'));

        $recipient = $this->makeUser('recipient');
        $this->actingAs($recipient)
            ->get(route('dashboard'))
            ->assertRedirect(route('recipient.dashboard'));

        $provider = $this->makeUser('provider');
        $this->actingAs($provider)
            ->get(route('dashboard'))
            ->assertRedirect(route('provider.dashboard'));
    }

    #[Test]
    public function dashboard_redirect_by_role_falls_back_to_default_dashboard_when_no_role(): void
    {
        config(['app.phone_verification_enabled' => false]);

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    private function makeUser(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_verified_at' => now(),
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
