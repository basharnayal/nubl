<?php

namespace Tests\Feature\I18n;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FR-18.1: English and Arabic UI support.
 * FR-18.2: Arabic content rendered with proper RTL layout.
 */
class RtlLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'donor', 'recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function arabic_locale_renders_html_with_dir_rtl_fr_18_2(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole('donor');

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('donor.dashboard'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    #[Test]
    public function english_locale_renders_html_with_dir_ltr_fr_18_2(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole('donor');

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('donor.dashboard'));

        $response->assertOk();
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);
    }

    #[Test]
    public function locale_switch_to_arabic_sets_session_and_redirects(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $user->assignRole('donor');

        $response = $this->actingAs($user)
            ->get(route('locale.switch', 'ar'));

        $response->assertRedirect();
        $this->assertSame('ar', session('locale'));
    }

    #[Test]
    public function guest_pages_render_rtl_for_arabic_locale(): void
    {
        $response = $this->withSession(['locale' => 'ar'])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
    }
}
