<?php

namespace Tests\Unit\Provider;

use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderMenuItemScopesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owned_by_limits_to_provider_user_id(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        ProviderMenuItem::create([
            'provider_id' => $a->id,
            'name' => 'A1',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $b->id,
            'name' => 'B1',
            'price' => 2,
            'category' => 'x',
            'is_active' => true,
        ]);

        $this->assertSame(1, ProviderMenuItem::ownedBy($a->id)->count());
        $this->assertTrue(ProviderMenuItem::ownedBy($a->id)->where('name', 'A1')->exists());
    }

    #[Test]
    public function active_scope_excludes_inactive_rows(): void
    {
        $provider = User::factory()->create();

        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'On',
            'price' => 1,
            'category' => 'x',
            'is_active' => true,
        ]);
        ProviderMenuItem::create([
            'provider_id' => $provider->id,
            'name' => 'Off',
            'price' => 2,
            'category' => 'x',
            'is_active' => false,
        ]);

        $this->assertSame(1, ProviderMenuItem::active()->where('provider_id', $provider->id)->count());
        $this->assertTrue(ProviderMenuItem::active()->where('name', 'On')->exists());
    }
}
