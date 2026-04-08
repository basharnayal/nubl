<?php

namespace Tests\Unit\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\RecipientAllowanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipientAllowanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function get_weekly_used_sums_city_funded_items_in_current_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        $recipient = User::factory()->create();
        $provider = User::factory()->create();

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'price' => 25.00,
            'category' => 'Food',
            'is_active' => true,
        ]);

        $req = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 100.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);
        $req->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 4,
            'price_snapshot' => 25.00,
        ]);

        $this->assertSame(100.0, RecipientAllowanceService::getWeeklyUsed($recipient->id));
    }

    #[Test]
    public function provider_adoption_requests_do_not_count_toward_weekly_used(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        $recipient = User::factory()->create();
        $provider = User::factory()->create();
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'price' => 200.00,
            'category' => 'Food',
            'is_active' => true,
        ]);

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 200.00,
            'status' => 'REQUESTED',
            'funding_source' => 'PROVIDER_ADOPTION',
        ])->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 200.00,
        ]);

        $this->assertSame(0.0, RecipientAllowanceService::getWeeklyUsed($recipient->id));
    }

    #[Test]
    public function get_remaining_limit_never_goes_negative(): void
    {
        config(['recipient.weekly_allowance_limit' => 100]);
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        $recipient = User::factory()->create();
        $provider = User::factory()->create();
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'price' => 150.00,
            'category' => 'Food',
            'is_active' => true,
        ]);

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 150.00,
            'status' => 'REDEEMABLE',
            'funding_source' => 'CITY_FUND',
        ])->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 150.00,
        ]);

        $this->assertSame(0.0, RecipientAllowanceService::getRemainingLimit($recipient->id));
    }

    #[Test]
    public function would_exceed_allowance_reflects_running_total(): void
    {
        config(['recipient.weekly_allowance_limit' => 400]);
        Carbon::setTestNow(Carbon::parse('2026-01-07 12:00:00', 'UTC'));

        $recipient = User::factory()->create();
        $provider = User::factory()->create();
        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Meal',
            'price' => 300.00,
            'category' => 'Food',
            'is_active' => true,
        ]);

        RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 300.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ])->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 300.00,
        ]);

        $this->assertFalse(RecipientAllowanceService::wouldExceedAllowance($recipient->id, 50.0));
        $this->assertTrue(RecipientAllowanceService::wouldExceedAllowance($recipient->id, 150.0));
    }
}
