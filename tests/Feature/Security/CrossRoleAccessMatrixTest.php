<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FR-1.5 Cross-Role Access Matrix.
 *
 * Verifies that each protected route group is inaccessible to users
 * who do not hold the required role. A donor must not reach admin,
 * provider, or recipient routes — and vice-versa.
 */
class CrossRoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.phone_verification_enabled' => false]);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'donor', 'recipient', 'provider'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    // Data Providers

    /**
     * Routes protected by `role:admin`.
     * Format: [routeName, httpMethod, routeParams].
     */
    public static function adminRoutes(): array
    {
        return [
            'admin.dashboard' => ['admin.dashboard', 'GET', []],
            'admin.users.pending' => ['admin.users.pending', 'GET', []],
            'admin.audit-logs.index' => ['admin.audit-logs.index', 'GET', []],
            'admin.finances.overview' => ['admin.finances.overview', 'GET', []],
            'admin.settings.qr.edit' => ['admin.settings.qr.edit', 'GET', []],
            'admin.settings.allowances.edit' => ['admin.settings.allowances.edit', 'GET', []],
            'admin.manage.users.index' => ['admin.manage.users.index', 'GET', []],
            'admin.roles.index' => ['admin.roles.index', 'GET', []],
            'admin.requests.index' => ['admin.requests.index', 'GET', []],
            'admin.menus.index' => ['admin.menus.index', 'GET', []],
        ];
    }

    /**
     * Routes protected by `role:donor`.
     */
    public static function donorRoutes(): array
    {
        return [
            'donor.dashboard' => ['donor.dashboard', 'GET', []],
            'donor.donations.index' => ['donor.donations.index', 'GET', []],
            'donor.donations.new' => ['donor.donations.new', 'GET', []],
            'donor.payments.success' => ['donor.payments.success', 'GET', []],
            'donor.payments.failed' => ['donor.payments.failed', 'GET', []],
        ];
    }

    /**
     * Routes protected by `role:provider`.
     */
    public static function providerRoutes(): array
    {
        return [
            'provider.dashboard' => ['provider.dashboard', 'GET', []],
            'provider.menu-items.index' => ['provider.menu-items.index', 'GET', []],
            'provider.requests.index' => ['provider.requests.index', 'GET', []],
            'provider.wallet.index' => ['provider.wallet.index', 'GET', []],
            'provider.qr.scan' => ['provider.qr.scan', 'GET', []],
        ];
    }

    /**
     * Routes protected by `role:recipient`.
     */
    public static function recipientRoutes(): array
    {
        return [
            'recipient.dashboard' => ['recipient.dashboard', 'GET', []],
            'recipient.providers.index' => ['recipient.providers.index', 'GET', []],
            'recipient.requests.index' => ['recipient.requests.index', 'GET', []],
        ];
    }

    // Tests: Admin routes blocked for non-admin roles

    #[Test]
    #[DataProvider('adminRoutes')]
    public function donor_cannot_access_admin_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('donor');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('adminRoutes')]
    public function provider_cannot_access_admin_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('provider');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('adminRoutes')]
    public function recipient_cannot_access_admin_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('recipient');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    // Tests: Donor routes blocked for non-donor roles

    #[Test]
    #[DataProvider('donorRoutes')]
    public function admin_cannot_access_donor_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('donorRoutes')]
    public function provider_cannot_access_donor_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('provider');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('donorRoutes')]
    public function recipient_cannot_access_donor_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('recipient');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    // Tests: Provider routes blocked for non-provider roles

    #[Test]
    #[DataProvider('providerRoutes')]
    public function admin_cannot_access_provider_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('providerRoutes')]
    public function donor_cannot_access_provider_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('donor');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('providerRoutes')]
    public function recipient_cannot_access_provider_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('recipient');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    // Tests: Recipient routes blocked for non-recipient roles

    #[Test]
    #[DataProvider('recipientRoutes')]
    public function admin_cannot_access_recipient_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('recipientRoutes')]
    public function donor_cannot_access_recipient_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('donor');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('recipientRoutes')]
    public function provider_cannot_access_recipient_routes(string $routeName, string $method, array $params): void
    {
        $user = $this->makeUser('provider');

        $this->actingAs($user)
            ->{strtolower($method)}(route($routeName, $params))
            ->assertForbidden();
    }

    // Tests: Guests blocked from all protected routes

    #[Test]
    #[DataProvider('adminRoutes')]
    public function guest_cannot_access_admin_routes(string $routeName, string $method, array $params): void
    {
        $this->{strtolower($method)}(route($routeName, $params))
            ->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('donorRoutes')]
    public function guest_cannot_access_donor_routes(string $routeName, string $method, array $params): void
    {
        $this->{strtolower($method)}(route($routeName, $params))
            ->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('providerRoutes')]
    public function guest_cannot_access_provider_routes(string $routeName, string $method, array $params): void
    {
        $this->{strtolower($method)}(route($routeName, $params))
            ->assertRedirect(route('login'));
    }

    #[Test]
    #[DataProvider('recipientRoutes')]
    public function guest_cannot_access_recipient_routes(string $routeName, string $method, array $params): void
    {
        $this->{strtolower($method)}(route($routeName, $params))
            ->assertRedirect(route('login'));
    }

    // Tests: Correct role CAN access their own routes (sanity check)

    #[Test]
    public function admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function donor_can_access_donor_dashboard(): void
    {
        $this->actingAs($this->makeUser('donor'))
            ->get(route('donor.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function recipient_can_access_recipient_dashboard(): void
    {
        $this->actingAs($this->makeUser('recipient'))
            ->get(route('recipient.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function provider_can_access_provider_dashboard(): void
    {
        $this->actingAs($this->makeUser('provider'))
            ->get(route('provider.dashboard'))
            ->assertOk();
    }

    // Helpers

    private function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'phone_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
