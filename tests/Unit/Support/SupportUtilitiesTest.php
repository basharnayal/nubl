<?php

namespace Tests\Unit\Support;

use App\Models\SystemSetting;
use App\Support\FinanceUi;
use App\Support\OperatingHoursNormalizer;
use App\Support\ProtectedRoles;
use App\Support\ProviderPayoutWeek;
use App\Support\QrTtl;
use App\Support\RecipientRequestSubmitCooldown;
use App\Support\SidebarPanel;
use App\Support\UiDateTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportUtilitiesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function finance_ui_uses_translation_when_defined(): void
    {
        app()->setLocale('en');

        $this->assertSame('Payment ID', FinanceUi::fieldLabel('payment_id'));
        $this->assertSame('Payment completed', FinanceUi::auditTitle('payment.succeeded'));
    }

    #[Test]
    public function finance_ui_falls_back_to_humanized_or_original_values_when_missing_translation(): void
    {
        app()->setLocale('en');

        $this->assertSame('Custom Key', FinanceUi::fieldLabel('custom_key'));
        $this->assertSame('unknown.event', FinanceUi::auditTitle('unknown.event'));
    }

    #[Test]
    public function protected_roles_returns_true_for_reserved_roles_only(): void
    {
        $this->assertTrue(ProtectedRoles::isProtected('admin'));
        $this->assertTrue(ProtectedRoles::isProtected('provider'));
        $this->assertFalse(ProtectedRoles::isProtected('ops_manager'));
    }

    #[Test]
    public function provider_payout_week_returns_previous_sunday_to_saturday_window(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);

        $runAt = Carbon::parse('2026-04-12 00:00:00', 'Asia/Riyadh'); // Sunday run

        $boundaries = ProviderPayoutWeek::settlementWeekBoundariesAt($runAt);

        $this->assertSame('2026-04-05 00:00:00', $boundaries['week_start_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-11 23:59:59', $boundaries['week_end_at']->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function recipient_request_submit_cooldown_tracks_remaining_time_and_can_be_cleared(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-21 12:00:00'));

        RecipientRequestSubmitCooldown::start(42, 30);

        $this->assertTrue(RecipientRequestSubmitCooldown::active(42));
        $remaining = RecipientRequestSubmitCooldown::secondsRemaining(42);
        $this->assertGreaterThanOrEqual(29, $remaining);
        $this->assertLessThanOrEqual(30, $remaining);

        Carbon::setTestNow(Carbon::parse('2026-04-21 12:00:31'));
        $this->assertFalse(RecipientRequestSubmitCooldown::active(42));

        RecipientRequestSubmitCooldown::clear(42);
        $this->assertSame(0, RecipientRequestSubmitCooldown::secondsRemaining(42));

        Carbon::setTestNow();
    }

    #[Test]
    public function ui_date_time_formats_in_app_timezone_with_12_hour_clock(): void
    {
        config(['app.timezone' => 'Asia/Riyadh']);
        app()->setLocale('en');

        $utcMidnight = Carbon::parse('2026-04-21 00:00:00', 'UTC');
        $formatted = UiDateTime::mediumWith12h($utcMidnight);

        $this->assertStringContainsString('2026', $formatted);
        $this->assertMatchesRegularExpression('/\d{1,2}:\d{2}\s(?:AM|PM)/', $formatted);
        $this->assertStringContainsString('3:00 AM', $formatted);
    }

    #[Test]
    public function operating_hours_normalizer_builds_expected_structure_for_open_and_closed_days(): void
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = ['closed' => true];
        }
        $hours['sunday'] = ['open' => '08:00', 'close' => '16:00', 'closed' => false];

        $request = Request::create('/', 'POST', ['operating_hours' => $hours]);

        $normalized = OperatingHoursNormalizer::fromRequest($request);

        $this->assertSame('08:00', $normalized['sunday']['open']);
        $this->assertSame('16:00', $normalized['sunday']['close']);
        $this->assertFalse($normalized['sunday']['closed']);
        $this->assertTrue($normalized['monday']['closed']);
    }

    #[Test]
    public function operating_hours_normalizer_throws_validation_error_for_incomplete_open_day(): void
    {
        $hours = [];
        foreach (array_keys(config('provider.weekdays')) as $day) {
            $hours[$day] = ['closed' => true];
        }
        $hours['tuesday'] = ['open' => '09:00', 'closed' => false];

        $request = Request::create('/', 'POST', ['operating_hours' => $hours]);

        try {
            OperatingHoursNormalizer::fromRequest($request);
            $this->fail('Expected ValidationException for incomplete operating day.');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('operating_hours.tuesday', $errors);
        }
    }

    #[Test]
    public function qr_ttl_uses_default_when_missing_and_clamps_db_override_to_bounds(): void
    {
        config([
            'qr.ttl_minutes' => 180,
            'qr.ttl_minutes_min' => 15,
            'qr.ttl_minutes_max' => 720,
        ]);

        $this->assertSame(180, QrTtl::currentMinutes());

        SystemSetting::setValue('qr.ttl_minutes', '-5');
        $this->assertSame(15, QrTtl::currentMinutes());

        SystemSetting::setValue('qr.ttl_minutes', '9999');
        $this->assertSame(720, QrTtl::currentMinutes());

        SystemSetting::setValue('qr.ttl_minutes', '90');
        $this->assertSame(90, QrTtl::currentMinutes());
    }

    #[Test]
    public function sidebar_panel_returns_actor_specific_menu_and_defaults_to_admin_for_unknown_actor(): void
    {
        $providerPanel = SidebarPanel::forActor('provider');
        $this->assertSame(__('Provider'), $providerPanel['title']);
        $this->assertSame('provider.dashboard', $providerPanel['items'][0]['provider_dashboard']['route_name']);

        $fallbackPanel = SidebarPanel::forActor('unknown-role');
        $this->assertSame(__('Admin'), $fallbackPanel['title']);
        $this->assertSame('admin.dashboard', $fallbackPanel['items'][0]['admin_dashboard']['route_name']);
    }
}
