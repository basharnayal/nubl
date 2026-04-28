<?php

namespace Tests\Unit\Support;

use App\Models\ProviderProfile;
use App\Models\SystemSetting;
use App\Support\ProviderDisplay;
use App\Support\PseudonymousRequestId;
use App\Support\RecipientAllowanceRetryCache;
use App\Support\RecipientFundRetryCache;
use App\Support\WeeklyAllowanceSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportHelpersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function provider_display_business_title_prefers_arabic_name_when_locale_is_arabic(): void
    {
        app()->setLocale('ar');

        $profile = new ProviderProfile([
            'business_name_ar' => 'مطعم الخير',
            'business_name_en' => 'Khair Restaurant',
        ]);

        $this->assertSame('مطعم الخير', ProviderDisplay::businessTitle($profile, 'Fallback'));
    }

    #[Test]
    public function provider_display_business_title_falls_back_to_english_arabic_then_fallback_name(): void
    {
        app()->setLocale('en');

        $withEnglish = new ProviderProfile([
            'business_name_en' => 'English Name',
            'business_name_ar' => 'Arabic Name',
        ]);
        $arabicOnly = new ProviderProfile([
            'business_name_ar' => 'Arabic Only',
        ]);

        $this->assertSame('English Name', ProviderDisplay::businessTitle($withEnglish, 'Fallback'));
        $this->assertSame('Arabic Only', ProviderDisplay::businessTitle($arabicOnly, 'Fallback'));
        $this->assertSame('Fallback', ProviderDisplay::businessTitle(null, 'Fallback'));
    }

    #[Test]
    public function provider_display_helpers_build_initials_labels_and_location_lines(): void
    {
        app()->setLocale('en');

        $this->assertSame('AB', ProviderDisplay::initials('abdu'));
        $this->assertSame('??', ProviderDisplay::initials('  '));

        $this->assertSame('Food truck', ProviderDisplay::businessCategoryLabel('food_truck'));
        $this->assertSame('Take away, Delivery', ProviderDisplay::serviceTypeLine(['take_away', 'delivery']));
        $this->assertSame('Grocery, Food truck', ProviderDisplay::businessCategoryLine(['grocery', ['name' => 'food_truck']]));

        $profileWithLocation = new ProviderProfile([
            'location' => '24.7,46.6',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
        ]);
        $profileWithoutLocation = new ProviderProfile([
            'city' => 'Example City',
            'region' => 'Example Region',
        ]);

        $this->assertSame('Unknown City', ProviderDisplay::cityLabel(null));
        $this->assertSame('My-City Name', ProviderDisplay::translatedCity('My-City Name'));
        $this->assertSame('24.7,46.6', ProviderDisplay::locationLine($profileWithLocation));
        $this->assertSame('Example City, Example Region', ProviderDisplay::locationLine($profileWithoutLocation));
    }

    #[Test]
    public function pseudonymous_request_id_is_prefixed_deterministic_and_unique_per_request_id(): void
    {
        config(['app.key' => 'base-test-app-key']);

        $a = PseudonymousRequestId::make(10);
        $b = PseudonymousRequestId::make(10);
        $c = PseudonymousRequestId::make(11);

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertMatchesRegularExpression('/^R-[A-F0-9]{8}$/', $a);
    }

    #[Test]
    public function recipient_allowance_retry_cache_stores_payload_enforces_lock_and_clears_both(): void
    {
        $payload = [
            'provider_id' => 77,
            'items' => [
                ['id' => 1, 'quantity' => 2],
            ],
        ];

        RecipientAllowanceRetryCache::storePayload(55, $payload);

        $this->assertSame($payload, RecipientAllowanceRetryCache::getPayload(55));
        $this->assertTrue(RecipientAllowanceRetryCache::tryScheduleJobLock(55, 30));
        $this->assertFalse(RecipientAllowanceRetryCache::tryScheduleJobLock(55, 30));

        RecipientAllowanceRetryCache::clear(55);

        $this->assertNull(RecipientAllowanceRetryCache::getPayload(55));
        $this->assertTrue(RecipientAllowanceRetryCache::tryScheduleJobLock(55, 30));
    }

    #[Test]
    public function recipient_fund_retry_cache_stores_payload_enforces_lock_and_clears_both(): void
    {
        $payload = [
            'provider_id' => 88,
            'items' => [
                ['id' => 9, 'quantity' => 1],
            ],
        ];

        RecipientFundRetryCache::storePayload(66, $payload);

        $this->assertSame($payload, RecipientFundRetryCache::getPayload(66));
        $this->assertTrue(RecipientFundRetryCache::tryScheduleJobLock(66, 30));
        $this->assertFalse(RecipientFundRetryCache::tryScheduleJobLock(66, 30));

        RecipientFundRetryCache::clear(66);

        $this->assertNull(RecipientFundRetryCache::getPayload(66));
        $this->assertTrue(RecipientFundRetryCache::tryScheduleJobLock(66, 30));
    }

    #[Test]
    public function weekly_allowance_settings_next_boundary_is_next_sunday_midnight_in_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);
        Carbon::setTestNow(Carbon::parse('2026-04-21 10:15:00', 'Asia/Riyadh')); // Tuesday

        $boundary = WeeklyAllowanceSettings::nextEffectiveBoundary();

        $this->assertSame('2026-04-26 00:00:00', $boundary->format('Y-m-d H:i:s')); // Next Sunday

        Carbon::setTestNow();
    }

    #[Test]
    public function weekly_allowance_settings_clear_pending_removes_only_pending_keys(): void
    {
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_ACTIVE, '400.00');
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE, '500.00');
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT, '2026-05-03T00:00:00+03:00');

        WeeklyAllowanceSettings::clearPending();

        $this->assertSame('400.00', SystemSetting::getValue(WeeklyAllowanceSettings::KEY_ACTIVE));
        $this->assertNull(SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_VALUE));
        $this->assertNull(SystemSetting::getValue(WeeklyAllowanceSettings::KEY_PENDING_EFFECTIVE_AT));
    }
}
