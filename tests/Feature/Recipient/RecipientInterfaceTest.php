<?php

namespace Tests\Feature\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecipientInterfaceTest extends TestCase
{
    use RefreshDatabase;

    protected $recipient;
    protected $provider;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        // Users
        $this->recipient = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_active' => true]);
        $this->recipient->assignRole('recipient');

        $this->provider = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => 'provider'
        ]);
        $this->provider->assignRole('provider');

        // Provider Profile
        \App\Models\ProviderProfile::create([
            'user_id' => $this->provider->id,
            'full_name_ar' => 'Prov Full Name AR',
            'full_name_en' => 'Prov Full Name EN',
            'phone_number' => '0501234567',
            'email' => 'prov@test.com',
            'business_name_ar' => 'Biz Name AR',
            'business_name_en' => 'Test Provider Business',
            'unified_number' => '920000000',
            'business_category' => ['Food'],
            'address_ar' => 'Addr AR',
            'address_en' => 'Addr EN',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => 'Downtown'
        ]);

        // Menu Item
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $this->provider->id,
            'name' => 'Burger',
            'price' => 50.00,
            'is_active' => true,
        ]);

        // Create a V2 Request
        $this->request = RequestModel::create([
            'recipient_id' => $this->recipient->id,
            'provider_id' => $this->provider->id,
            'reserved_amount' => 50.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        $this->request->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 50.00,
        ]);
    }

    #[Test]
    public function recipient_can_view_requests_index()
    {
        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($this->recipient)
            ->get(route('recipient.requests.index'));

        $response->assertStatus(200);
        $response->assertSee('My Requests');
        $response->assertSee('Test Provider Business'); // business name on list (ProviderDisplay)
        $response->assertSee('50.00');
        $response->assertSee('SAR');
    }

    #[Test]
    public function recipient_can_view_request_details()
    {
        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($this->recipient)
            ->get(route('recipient.requests.show', $this->request->id));

        $response->assertStatus(200);
        $response->assertSee('Request Details');
        $response->assertSee('Burger'); // Item Name
        $response->assertSee('50.00 SAR');
    }

    #[Test]
    public function recipient_can_view_providers_list()
    {
        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($this->recipient)
            ->get(route('recipient.providers.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Provider Business');
    }

    #[Test]
    public function recipient_can_cancel_requested_request()
    {
        $response = $this->actingAs($this->recipient)
            ->post(route('recipient.requests.cancel', $this->request->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->request->refresh();

        $this->assertSame('CANCELLED', $this->request->status);

        $activity = Activity::query()
            ->where('description', 'request.status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->recipient->id, $activity->causer_id);
        $this->assertSame('status_changed', $activity->properties->get('action'));
        $this->assertSame($this->request->id, $activity->properties->get('request_id'));
        $this->assertSame($this->recipient->id, $activity->properties->get('recipient_id'));
        $this->assertSame('REQUESTED', $activity->properties->get('from'));
        $this->assertSame('CANCELLED', $activity->properties->get('to'));

        $this->provider->refresh();
        $this->assertCount(1, $this->provider->notifications);
        $this->assertSame('provider_request_status_changed', $this->provider->notifications->first()->data['type']);
        $this->assertSame('CANCELLED', $this->provider->notifications->first()->data['status']);

        // Cancelling a CITY_FUND REQUESTED order must free the weekly allowance
        $used = \App\Services\RecipientAllowanceService::getWeeklyUsed($this->recipient->id);
        $this->assertSame(0.0, $used, 'Cancelled request must not count toward weekly allowance.');
    }
}
