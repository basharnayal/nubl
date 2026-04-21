<?php

namespace Tests\Feature\Public;

use App\Services\LandingPageStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LandingPageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function landing_page_passes_hero_stats_from_service_to_view(): void
    {
        $heroStats = [
            'totalDelivered' => 100,
            'familiesSupported' => 2,
            'localProviders' => 3,
            'feedItems' => [],
            'trustLedger' => ['is_live' => false, 'rows' => [], 'shown' => 0, 'total' => 0],
            'trustBadges' => null,
            'providerCounts' => ['grocery' => 1, 'catering' => 1, 'bakery' => 1, 'restaurant' => 0],
        ];

        $mock = Mockery::mock(LandingPageStatsService::class);
        $mock->shouldReceive('getHeroStats')->once()->andReturn($heroStats);
        $this->app->instance(LandingPageStatsService::class, $mock);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertViewHas('heroStats', $heroStats);
    }
}

