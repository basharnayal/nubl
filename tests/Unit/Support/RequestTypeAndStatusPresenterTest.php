<?php

namespace Tests\Unit\Support;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\RequestItem;
use App\Models\User;
use App\Support\RecipientRequestStatusPresenter;
use App\Support\RequestTypeLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequestTypeAndStatusPresenterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function request_type_label_prefers_menu_category_name_when_available(): void
    {
        [$request, $menuItem] = $this->createRequestWithMenuItem('Bakery', 'Bread Item', 'meal');
        $menuItem->update(['category_id' => $this->categoryId('restaurant', 'Meals')]);

        RequestItem::create([
            'request_id' => $request->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 10.00,
        ]);

        $label = RequestTypeLabel::forRequest($request->fresh('items.menuItem.menuItemCategory'));

        $this->assertSame('Meals', $label);
    }

    #[Test]
    public function request_type_label_falls_back_to_item_name_when_category_name_is_numeric(): void
    {
        [$request, $menuItem] = $this->createRequestWithMenuItem('Fallback Item', 'Fallback Item', 'meal');
        $menuItem->update(['category_id' => $this->categoryId('restaurant', '12345')]);

        RequestItem::create([
            'request_id' => $request->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 10.00,
        ]);

        $label = RequestTypeLabel::forRequest($request->fresh('items.menuItem.menuItemCategory'));

        $this->assertSame('Fallback Item', $label);
    }

    #[Test]
    public function request_type_label_falls_back_to_legacy_category_mapping(): void
    {
        [$request, $menuItem] = $this->createRequestWithMenuItem('12345', '12345', 'basket');
        $menuItem->update(['category_id' => null]);

        RequestItem::create([
            'request_id' => $request->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
            'price_snapshot' => 10.00,
        ]);

        $label = RequestTypeLabel::forRequest($request->fresh('items.menuItem.menuItemCategory'));

        $this->assertSame((string) __('Food basket'), $label);
    }

    #[Test]
    public function request_type_label_returns_generic_request_when_no_items_exist(): void
    {
        $request = $this->createRequestOnly();

        $label = RequestTypeLabel::forRequest($request->fresh('items.menuItem.menuItemCategory'));

        $this->assertSame((string) __('Request'), $label);
    }

    #[Test]
    public function status_presenter_returns_expected_card_for_requested_state(): void
    {
        $request = new RequestModel(['status' => 'REQUESTED']);

        $card = RecipientRequestStatusPresenter::card($request, true);

        $this->assertSame('primary', $card['accent']);
        $this->assertSame('check', $card['heroIcon']);
        $this->assertSame('requested', $card['footer']);
        $this->assertCount(4, $card['steps']);
        $this->assertSame('done', $card['steps'][0]['state']);
    }

    #[Test]
    public function status_presenter_returns_admin_pending_card_with_simple_footer(): void
    {
        $request = new RequestModel(['status' => 'ADMIN_PENDING']);

        $card = RecipientRequestStatusPresenter::card($request, false);

        $this->assertSame('warning', $card['accent']);
        $this->assertSame('clock', $card['heroIcon']);
        $this->assertSame('simple', $card['footer']);
        $this->assertSame('recipient.request_progress.badge_admin_pending', $card['badgeLabelKey']);
        $this->assertGreaterThanOrEqual(4, count($card['steps']));
    }

    #[Test]
    public function status_presenter_fallback_keeps_raw_status_when_state_is_unknown(): void
    {
        $request = new RequestModel(['status' => 'SOME_NEW_STATUS']);

        $card = RecipientRequestStatusPresenter::card($request, false);

        $this->assertSame('slate', $card['accent']);
        $this->assertNull($card['badgeLabelKey']);
        $this->assertSame('SOME NEW STATUS', $card['badgeStatusRaw']);
        $this->assertSame('simple', $card['footer']);
        $this->assertCount(4, $card['steps']);
    }

    #[Test]
    public function status_presenter_step_labels_are_fixed_four_steps(): void
    {
        $this->assertCount(4, RecipientRequestStatusPresenter::STEP_LABELS);
        $this->assertSame('recipient.request_progress.step1', RecipientRequestStatusPresenter::STEP_LABELS[0]['label']);
    }

    /**
     * @return array{RequestModel, ProviderMenuItem}
     */
    private function createRequestWithMenuItem(string $itemName, string $menuName, string $legacyCategory): array
    {
        $request = $this->createRequestOnly();
        $provider = User::findOrFail($request->provider_id);

        $menuItem = ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => $menuName,
            'description' => null,
            'price' => 10.00,
            'category' => $legacyCategory,
            'sku' => null,
            'max_per_request' => null,
            'category_id' => null,
            'is_active' => true,
            'is_admin_blocked' => false,
        ]);

        $menuItem->update(['name' => $itemName]);

        return [$request, $menuItem];
    }

    private function createRequestOnly(): RequestModel
    {
        $recipient = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
        ]);
        $provider = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);

        return RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $provider->id,
            'reserved_amount' => 10.00,
            'status' => 'REQUESTED',
        ]);
    }

    private function categoryId(string $businessCategory, string $name): int
    {
        return MenuItemCategory::create([
            'business_category' => $businessCategory,
            'name' => $name,
            'slug' => strtolower($name).'-'.uniqid(),
            'is_active' => true,
        ])->id;
    }
}

