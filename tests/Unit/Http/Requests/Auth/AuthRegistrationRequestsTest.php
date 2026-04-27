<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\StoreProviderRegistrationRequest;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Http\Requests\Auth\UpdateResubmitApplicationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuthRegistrationRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function store_registered_user_request_allows_guests_only(): void
    {
        $guestRequest = $this->makeRequest(StoreRegisteredUserRequest::class, []);
        $authRequest = $this->makeRequest(StoreRegisteredUserRequest::class, [], User::factory()->create());

        $this->assertTrue($guestRequest->authorize());
        $this->assertFalse($authRequest->authorize());
    }

    #[Test]
    public function store_registered_user_request_for_donor_uses_donor_rule_set(): void
    {
        $request = $this->makeRequest(StoreRegisteredUserRequest::class, [
            'membership_type' => User::MEMBERSHIP_DONOR,
        ]);

        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('phone_number', $rules);
        $this->assertArrayNotHasKey('id_photo_base64', $rules);
    }

    #[Test]
    public function store_registered_user_request_for_recipient_requires_base64_identity_documents(): void
    {
        $request = $this->makeRequest(StoreRegisteredUserRequest::class, [
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);

        $rules = $request->rules();

        $this->assertArrayHasKey('id_photo_base64', $rules);
        $this->assertArrayNotHasKey('address_confirmation_base64', $rules);
        $this->assertArrayHasKey('income_band', $rules);
        $this->assertArrayHasKey('marital_status', $rules);
    }

    #[Test]
    public function store_registered_user_request_rejects_file_upload_for_recipient_flow(): void
    {
        $request = $this->makeRequest(
            StoreRegisteredUserRequest::class,
            ['membership_type' => User::MEMBERSHIP_RECIPIENT],
            null,
            [],
            ['id_photo' => UploadedFile::fake()->image('id.jpg')]
        );

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        try {
            $validator->fails();
            $this->fail('Expected HttpException to be thrown for recipient file upload.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    #[Test]
    public function store_provider_registration_request_allows_guests_only(): void
    {
        $guestRequest = $this->makeRequest(StoreProviderRegistrationRequest::class, []);
        $authRequest = $this->makeRequest(StoreProviderRegistrationRequest::class, [], User::factory()->create());

        $this->assertTrue($guestRequest->authorize());
        $this->assertFalse($authRequest->authorize());
    }

    #[Test]
    public function store_provider_registration_prepare_for_validation_normalizes_valid_phone_numbers(): void
    {
        $request = $this->makeRequest(StoreProviderRegistrationRequest::class, [
            'phone_number' => '+966512345678',
        ]);

        $method = new \ReflectionMethod(StoreProviderRegistrationRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertSame('512345678', $request->input('phone_number'));
    }

    #[Test]
    public function store_provider_registration_with_validator_adds_operating_hours_errors_when_day_is_incomplete(): void
    {
        $payload = $this->validProviderPayload();
        $payload['operating_hours']['monday'] = [
            'open' => '09:00',
            'closed' => false,
        ];

        $request = $this->makeRequest(StoreProviderRegistrationRequest::class, $payload, null, [], [
            'business_license' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
            'id_or_iqama' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('operating_hours.monday', $validator->errors()->toArray());
    }

    #[Test]
    public function provider_registration_document_limits_use_laravel_kilobytes_and_require_category_selection(): void
    {
        config(['provider.document_max_size_mb' => 5]);

        $payload = $this->validProviderPayload();
        $payload['business_category'] = [];

        $request = $this->makeRequest(StoreProviderRegistrationRequest::class, $payload, null, [], [
            'business_license' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
            'id_or_iqama' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ]);

        $rules = $request->rules();

        $this->assertContains('max:5120', $rules['business_license']);
        $this->assertContains('max:5120', $rules['id_or_iqama']);
        $this->assertContains('min:1', $rules['business_category']);

        $validator = Validator::make($request->all(), $rules);
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('business_category', $validator->errors()->toArray());
    }

    #[Test]
    public function update_resubmit_application_request_redirects_if_user_is_not_rejected(): void
    {
        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);

        $request = $this->makeRequest(UpdateResubmitApplicationRequest::class, [], $user);

        $this->expectException(HttpResponseException::class);
        $request->authorize();
    }

    #[Test]
    public function update_resubmit_application_for_recipient_applies_base64_validation_in_after_hook(): void
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_REJECTED,
        ]);
        $recipient->assignRole('recipient');

        $request = $this->makeRequest(UpdateResubmitApplicationRequest::class, [
            'name' => 'Recipient Name',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Address',
            'id_type' => 'national_id',
            'id_number' => '1234567890',
            'income_band' => '1000-1500',
            'household_size' => 3,
            'marital_status' => 'single',
            'is_student' => '1',
            'employment_status' => 'unemployed',
            'situation_description' => 'Recipient needs assistance for food support.',
            'id_photo_base64' => 'invalid-base64',
        ], $recipient);

        $this->assertTrue($request->authorize());

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('id_photo_base64', $validator->errors()->toArray());
    }

    #[Test]
    public function update_resubmit_application_for_provider_normalizes_operating_hours(): void
    {
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_REJECTED,
        ]);
        $provider->assignRole('provider');

        $payload = [
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
            'daily_capacity' => 100,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'bank_name' => 'Bank',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Provider Name',
            'operating_hours' => $this->validOperatingHours(),
        ];

        $request = $this->makeRequest(UpdateResubmitApplicationRequest::class, $payload, $provider);

        $this->assertTrue($request->authorize());

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));

        $normalized = $request->normalizedOperatingHours();
        $this->assertSame('09:00', $normalized['sunday']['open']);
        $this->assertSame('17:00', $normalized['sunday']['close']);
        $this->assertFalse($normalized['sunday']['closed']);
    }

    #[Test]
    public function provider_resubmission_document_limits_use_laravel_kilobytes(): void
    {
        config(['provider.document_max_size_mb' => 5]);

        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_REJECTED,
        ]);
        $provider->assignRole('provider');

        $payload = $this->validProviderPayload();
        unset($payload['phone_number'], $payload['email']);

        $request = $this->makeRequest(UpdateResubmitApplicationRequest::class, $payload, $provider);
        $rules = $request->rules();

        $this->assertContains('max:5120', $rules['business_license']);
        $this->assertContains('max:5120', $rules['id_or_iqama']);
    }

    /**
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $class
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $routeParameters
     * @param  array<string, UploadedFile>  $files
     */
    private function makeRequest(
        string $class,
        array $data,
        ?User $user = null,
        array $routeParameters = [],
        array $files = []
    ) {
        $request = $class::create('/', 'POST', $data, [], $files);
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

    /**
     * @return array<string, mixed>
     */
    private function validProviderPayload(): array
    {
        return [
            'full_name_ar' => 'Provider AR',
            'full_name_en' => 'Provider EN',
            'phone_number' => '0512345678',
            'email' => 'provider-'.uniqid().'@example.com',
            'business_name_ar' => 'Business AR',
            'business_name_en' => 'Business EN',
            'unified_number' => '7000000001',
            'business_category' => ['restaurant'],
            'address_ar' => 'Address AR',
            'address_en' => 'Address EN',
            'city' => array_key_first(config('provider.cities')),
            'region' => array_key_first(config('provider.regions')),
            'location' => null,
            'daily_capacity' => 100,
            'service_type' => ['meal_preparation'],
            'estimated_preparation_order_time' => '30 minutes',
            'adoption_support' => 'yes',
            'bank_name' => 'Bank',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Provider Name',
            'password' => 'ComplexPass123!',
            'operating_hours' => $this->validOperatingHours(),
        ];
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
