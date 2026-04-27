<?php

namespace Tests\Unit\Http\Requests\Admin;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ProviderProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserRoleRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'donor', 'recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    #[Test]
    public function store_role_request_has_expected_rules_and_custom_message(): void
    {
        $request = $this->makeRequest(StoreRoleRequest::class);

        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        $this->assertContains('unique:roles,name', $rules['name']);
        $this->assertContains('regex:/^[a-z][a-z0-9_-]*$/', $rules['name']);
        $this->assertArrayHasKey('permissions.*', $rules);
        $this->assertArrayHasKey('name.regex', $request->messages());
    }

    #[Test]
    public function update_role_request_does_not_allow_name_change_for_protected_roles(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $request = $this->makeRequest(UpdateRoleRequest::class, [], null, ['role' => $role]);
        $rules = $request->rules();

        $this->assertArrayNotHasKey('name', $rules);
        $this->assertArrayHasKey('permissions', $rules);
    }

    #[Test]
    public function update_role_request_requires_unique_name_for_non_protected_roles(): void
    {
        $role = Role::create(['name' => 'ops_manager', 'guard_name' => 'web']);

        $request = $this->makeRequest(UpdateRoleRequest::class, [], null, ['role' => $role]);
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertTrue(
            collect($rules['name'])->contains(fn ($rule) => $rule instanceof Unique),
            'Expected unique rule on role name for non-protected roles.'
        );
    }

    #[Test]
    public function store_user_request_authorize_requires_admin_role(): void
    {
        $nonAdmin = User::factory()->create();

        $authorizedRequest = $this->makeRequest(StoreUserRequest::class, [], $this->admin);
        $forbiddenRequest = $this->makeRequest(StoreUserRequest::class, [], $nonAdmin);

        $this->assertTrue($authorizedRequest->authorize());
        $this->assertFalse($forbiddenRequest->authorize());
    }

    #[Test]
    public function store_user_request_recipient_membership_includes_recipient_specific_rules(): void
    {
        $request = $this->makeRequest(StoreUserRequest::class, [
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ], $this->admin);

        $rules = $request->rules();

        $this->assertArrayHasKey('phone_number', $rules);
        $this->assertArrayHasKey('nationality', $rules);
        $this->assertArrayHasKey('id_photo', $rules);
        $this->assertContains('required', $rules['id_photo']);
        $this->assertArrayHasKey('income_band', $rules);
        $this->assertArrayHasKey('household_size', $rules);
        $this->assertArrayNotHasKey('address_confirmation', $rules);
    }

    #[Test]
    public function store_user_request_provider_membership_includes_provider_specific_rules(): void
    {
        $request = $this->makeRequest(StoreUserRequest::class, [
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ], $this->admin);

        $rules = $request->rules();

        $this->assertArrayHasKey('phone_number', $rules);
        $this->assertArrayHasKey('business_name_ar', $rules);
        $this->assertArrayHasKey('business_license', $rules);
        $this->assertContains('required', $rules['business_license']);
        $this->assertArrayHasKey('id_or_iqama', $rules);
        $this->assertArrayHasKey('service_type.*', $rules);
        $this->assertArrayHasKey('adoption_support', $rules);
        $this->assertArrayHasKey('iban', $rules);
    }

    #[Test]
    public function update_user_request_for_provider_includes_operating_hours_and_optional_password_rule(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $this->createProviderProfile($provider);

        $request = $this->makeRequest(
            UpdateUserRequest::class,
            ['password' => 'Secret123!'],
            $this->admin,
            ['user' => $provider]
        );

        $rules = $request->rules();

        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('operating_hours', $rules);
        $this->assertArrayHasKey('operating_hours.sunday', $rules);
        $this->assertArrayHasKey('business_license', $rules);
        $this->assertContains('nullable', $rules['business_license']);
        $this->assertContains('required', $rules['membership_type']);
        $this->assertContains('in:provider', $rules['membership_type']);
    }

    #[Test]
    public function update_user_request_for_recipient_includes_recipient_edit_rules_when_profile_exists(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        RecipientProfile::create([
            'user_id' => $recipient->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Short address',
            'id_type' => RecipientProfile::ID_TYPES[0],
            'id_photo_path' => 'recipient/id.jpg',
        ]);

        $request = $this->makeRequest(
            UpdateUserRequest::class,
            [],
            $this->admin,
            ['user' => $recipient]
        );

        $rules = $request->rules();

        $this->assertArrayHasKey('nationality', $rules);
        $this->assertArrayHasKey('id_photo', $rules);
        $this->assertContains('nullable', $rules['id_photo']);
        $this->assertArrayNotHasKey('address_confirmation', $rules);
    }

    /**
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $class
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $routeParameters
     */
    private function makeRequest(string $class, array $data = [], ?User $user = null, array $routeParameters = [])
    {
        $request = $class::create('/', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $request->setUserResolver(fn () => $user);

        $uri = '/';
        if (! empty($routeParameters)) {
            $segments = array_map(static fn (string $key): string => '{'.$key.'}', array_keys($routeParameters));
            $uri = '/'.implode('/', $segments);
        }

        $route = new Route('POST', $uri, []);
        $route->bind($request);
        foreach ($routeParameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function createProviderProfile(User $provider): void
    {
        ProviderProfile::create([
            'user_id' => $provider->id,
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '966511111111',
            'email' => $provider->email,
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
        ]);
    }
}
