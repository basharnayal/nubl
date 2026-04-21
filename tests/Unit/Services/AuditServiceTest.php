<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuditService;
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

        // AuditService formats description as "{entity}.{action}"
        $this->assertSame('user.updated', $latest->description);

        $props = $latest->properties?->toArray() ?? [];
        $this->assertSame('user', $props['entity'] ?? null);
        $this->assertSame('updated', $props['action'] ?? null);
        $this->assertSame(5, $props['user_id'] ?? null);

        // Causer must be the user whose ID was passed explicitly
        $this->assertSame($user->id, $latest->causer_id);

        // FR-13.2: every audit entry must carry a SHA-256 hash
        $this->assertNotNull($latest->sha256_hash, 'sha256_hash must be set on every audit entry (FR-13.2).');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $latest->sha256_hash);
    }
}
