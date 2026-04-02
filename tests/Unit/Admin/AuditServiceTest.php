<?php

namespace Tests\Unit\Admin;

use App\Http\Services\AuditService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function log_persists_activity_with_entity_and_action(): void
    {
        $user = User::factory()->create();
        $audit = app(AuditService::class);

        $audit->log('user', 'updated', ['user_id' => 5], $user->id);

        $table = config('activitylog.table_name');
        $this->assertTrue(Schema::hasTable($table));

        $this->assertSame(1, Activity::query()->count());
        $latest = Activity::query()->latest('id')->first();
        $this->assertStringContainsString('user', (string) $latest->description);
        $props = $latest->properties?->toArray() ?? [];
        $this->assertSame('user', $props['entity'] ?? null);
        $this->assertSame('updated', $props['action'] ?? null);
    }
}
