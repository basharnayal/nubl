<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Recipient\RecipientController;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecipientControllerDirectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function providers_list_returns_only_active_providers_with_profiles(): void
    {
        $activeProvider = User::factory()->create([
            'name' => 'A Provider',
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        ProviderProfile::create([
            'user_id' => $activeProvider->id,
            'full_name_ar' => 'مزود',
            'full_name_en' => 'Provider',
            'phone_number' => '966500000901',
            'email' => 'active-provider@example.test',
            'business_name_ar' => 'نشط',
            'business_name_en' => 'Active Provider',
            'unified_number' => '7000000901',
            'business_category' => ['Restaurant'],
            'address_ar' => 'عنوان',
            'address_en' => 'Address',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
        ]);

        User::factory()->create([
            'name' => 'No Profile',
            'membership_type' => User::MEMBERSHIP_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Donor User',
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $view = app(RecipientController::class)->providersList();

        $this->assertSame('recipient.providers-list', $view->name());
        $providers = $view->getData()['providers'];
        $this->assertCount(1, $providers);
        $this->assertSame($activeProvider->id, $providers->first()->id);
    }
}

