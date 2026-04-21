<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ProfilePhotoRequest;
use App\Http\Requests\Provider\IndexProviderRequestsRequest;
use App\Http\Requests\Provider\RedeemProviderQrRequest;
use App\Http\Requests\Provider\StoreMenuItemRequest;
use App\Http\Requests\Provider\StoreProviderProofRequest;
use App\Http\Requests\Provider\UpdateMenuItemRequest;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Http\Requests\Provider\UpdateProviderRequestActionRequest;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Http\Requests\UpdateProviderBusinessProfileRequest;
use App\Http\Requests\UpdateProviderFinancialProfileRequest;
use App\Models\MenuItemCategory;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderAndProfileRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['provider', 'recipient', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function profile_photo_request_requires_authenticated_user(): void
    {
        $guestRequest = $this->makeRequest(ProfilePhotoRequest::class, []);
        $authRequest = $this->makeRequest(ProfilePhotoRequest::class, [], User::factory()->create());

        $this->assertFalse($guestRequest->authorize());
        $this->assertTrue($authRequest->authorize());

        $rules = $authRequest->rules();
        $this->assertArrayHasKey('profile_logo', $rules);
        $this->assertArrayHasKey('remove_profile_logo', $rules);
    }

    #[Test]
    public function update_provider_business_profile_requires_provider_user_with_existing_profile(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $withoutProfile = $this->makeRequest(UpdateProviderBusinessProfileRequest::class, [], $provider);
        $this->assertFalse($withoutProfile->authorize());

        $this->createProviderProfile($provider);
        $provider = $provider->fresh();
        $withProfile = $this->makeRequest(UpdateProviderBusinessProfileRequest::class, [
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '0512345678',
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
        ], $provider);

        $this->assertTrue($withProfile->authorize());

        $validator = Validator::make($withProfile->all(), $withProfile->rules());
        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
        $withProfile->setValidator($validator);

        $payload = $withProfile->businessProfilePayload();
        $this->assertSame('966512345678', $payload['phone_normalized']);
        $this->assertSame('restaurant', $payload['business_category'][0]);
    }

    #[Test]
    public function update_provider_financial_profile_requires_provider_user_with_financial_record(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $provider->assignRole('provider');

        $withoutInfo = $this->makeRequest(UpdateProviderFinancialProfileRequest::class, [], $provider);
        $this->assertFalse($withoutInfo->authorize());

        ProviderFinancialInfo::create([
            'user_id' => $provider->id,
            'bank_name' => 'Bank',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Provider Name',
        ]);

        $withInfo = $this->makeRequest(UpdateProviderFinancialProfileRequest::class, [], $provider->fresh());
        $this->assertTrue($withInfo->authorize());
        $this->assertArrayHasKey('bank_name', $withInfo->rules());
        $this->assertArrayHasKey('iban', $withInfo->rules());
    }

    #[Test]
    public function provider_index_and_action_requests_have_expected_filters_and_validation(): void
    {
        $provider = User::factory()->create(['membership_type' => User::MEMBERSHIP_PROVIDER]);
        $provider->assignRole('provider');

        $indexRequest = $this->makeRequest(IndexProviderRequestsRequest::class, [], $provider);
        $this->assertTrue($indexRequest->authorize());
        $this->assertArrayHasKey('funding_source', $indexRequest->rules());
        $this->assertArrayHasKey('per_page', $indexRequest->rules());

        $actionRequest = $this->makeRequest(UpdateProviderRequestActionRequest::class, [
            'action' => 'reject',
        ], $provider);
        $this->assertTrue($actionRequest->authorize());

        $validator = Validator::make($actionRequest->all(), $actionRequest->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rejection_reason_code', $validator->errors()->toArray());
    }

    #[Test]
    public function provider_qr_and_proof_requests_define_expected_rules(): void
    {
        $provider = User::factory()->create(['membership_type' => User::MEMBERSHIP_PROVIDER]);
        $provider->assignRole('provider');

        $qrRequest = $this->makeRequest(RedeemProviderQrRequest::class, [], $provider);
        $proofRequest = $this->makeRequest(StoreProviderProofRequest::class, [], $provider);

        $this->assertTrue($qrRequest->authorize());
        $this->assertArrayHasKey('token', $qrRequest->rules());

        $validator = Validator::make([], $qrRequest->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('token', $validator->errors()->toArray());

        $this->assertTrue($proofRequest->authorize());
        $this->assertArrayHasKey('proof_file', $proofRequest->rules());
        $this->assertArrayHasKey('proof_photo_base64', $proofRequest->rules());

        $proofValidator = Validator::make([
            'proof_photo_base64' => 'not-a-base64-image',
        ], $proofRequest->rules());
        $this->assertTrue($proofValidator->fails());
        $this->assertArrayHasKey('proof_photo_base64', $proofValidator->errors()->toArray());
    }

    #[Test]
    public function update_provider_profile_builds_normalized_operating_hours(): void
    {
        $provider = User::factory()->create(['membership_type' => User::MEMBERSHIP_PROVIDER]);
        $provider->assignRole('provider');

        $request = $this->makeRequest(UpdateProviderProfileRequest::class, [
            'daily_capacity' => 100,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'operating_hours' => $this->validOperatingHours(),
        ], $provider);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));

        $normalized = $request->buildOperatingHours();
        $this->assertSame('09:00', $normalized['sunday']['open']);
        $this->assertFalse($normalized['sunday']['closed']);
    }

    #[Test]
    public function store_menu_item_request_rejects_categories_that_do_not_match_provider_business_type(): void
    {
        $provider = $this->createProviderUser();
        $category = MenuItemCategory::create([
            'business_category' => 'bakery',
            'name' => 'Bakery',
            'slug' => 'bakery',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(StoreMenuItemRequest::class, [
            'name' => 'Item',
            'category_id' => $category->id,
            'price' => 10,
        ], $provider);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function update_menu_item_request_allows_other_category_even_when_provider_has_different_business_type(): void
    {
        $provider = $this->createProviderUser();
        $category = MenuItemCategory::create([
            'business_category' => 'other',
            'name' => 'Other',
            'slug' => 'other',
            'is_active' => true,
        ]);

        $request = $this->makeRequest(UpdateMenuItemRequest::class, [
            'name' => 'Item',
            'category_id' => $category->id,
            'price' => 10,
        ], $provider);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    #[Test]
    public function store_recipient_request_requires_active_recipient_role_and_full_access(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $pendingRecipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
        $pendingRecipient->assignRole('recipient');

        $authorized = $this->makeRequest(StoreRecipientRequest::class, [], $recipient);
        $blocked = $this->makeRequest(StoreRecipientRequest::class, [], $pendingRecipient);

        $this->assertTrue($authorized->authorize());
        $this->assertFalse($blocked->authorize());
    }

    #[Test]
    public function store_recipient_request_after_hook_rejects_inactive_provider_and_invalid_item_constraints(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $inactiveProvider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => false,
            'accepting_orders' => true,
        ]);
        $inactiveProvider->assignRole('provider');
        $item = ProviderMenuItem::create([
            'provider_id' => $inactiveProvider->id,
            'name' => 'Inactive Provider Item',
            'price' => 10,
            'is_active' => true,
        ]);

        $request = $this->makeRequest(StoreRecipientRequest::class, [
            'provider_id' => $inactiveProvider->id,
            'items' => [
                ['id' => $item->id, 'quantity' => 1],
            ],
        ], $recipient);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('provider_id', $validator->errors()->toArray());
    }

    #[Test]
    public function store_recipient_request_after_hook_rejects_wrong_owner_and_over_quantity_items(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $provider = $this->createOpenProviderWithCapacity();
        $otherProvider = $this->createOpenProviderWithCapacity();

        $foreignItem = ProviderMenuItem::create([
            'provider_id' => $otherProvider->id,
            'name' => 'Foreign Item',
            'price' => 10,
            'max_per_request' => 2,
            'is_active' => true,
        ]);

        $ownItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Own Item',
            'price' => 10,
            'max_per_request' => 1,
            'is_active' => true,
        ]);

        $request = $this->makeRequest(StoreRecipientRequest::class, [
            'provider_id' => $provider->id,
            'items' => [
                ['id' => $foreignItem->id, 'quantity' => 1],
                ['id' => $ownItem->id, 'quantity' => 5],
            ],
        ], $recipient);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.id', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.1.quantity', $validator->errors()->toArray());
    }

    #[Test]
    public function store_recipient_request_returns_validation_errors_for_malformed_items_payload(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $recipient->assignRole('recipient');

        $provider = $this->createOpenProviderWithCapacity();

        $request = $this->makeRequest(StoreRecipientRequest::class, [
            'provider_id' => $provider->id,
            'items' => 'not-an-array',
        ], $recipient);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
    }

    /**
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $class
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $routeParameters
     */
    private function makeRequest(string $class, array $data, ?User $user = null, array $routeParameters = [])
    {
        $request = $class::create('/', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $user);

        $route = new Route('POST', '/', []);
        foreach ($routeParameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function createProviderUser(): User
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'accepting_orders' => true,
        ]);
        $provider->assignRole('provider');

        $this->createProviderProfile($provider);

        return $provider;
    }

    private function createOpenProviderWithCapacity(): User
    {
        $provider = $this->createProviderUser();

        ProviderOperatingInfo::create([
            'user_id' => $provider->id,
            'operating_hours' => $this->validOperatingHours(),
            'daily_capacity' => 100,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'pickup_notes' => null,
        ]);

        return $provider;
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

    /**
     * @return array<string, array<string, mixed>>
     */
    private function validOperatingHours(): array
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = ['open' => '09:00', 'close' => '17:00', 'closed' => false];
        }

        return $hours;
    }
}
