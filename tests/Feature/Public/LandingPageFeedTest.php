<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LandingPageFeedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function landing_feed_json_returns_items_array(): void
    {
        $response = $this->getJson(route('landing.feed'));

        $response->assertOk();
        $response->assertJsonStructure([
            'items',
            'totalDelivered',
            'familiesSupported',
            'localProviders',
        ]);
        $this->assertIsArray($response->json('items'));
        $this->assertIsInt($response->json('totalDelivered'));
        $this->assertIsInt($response->json('familiesSupported'));
        $this->assertIsInt($response->json('localProviders'));
    }
}
