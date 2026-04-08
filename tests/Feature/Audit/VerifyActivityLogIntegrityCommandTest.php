<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyActivityLogIntegrityCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function verify_command_succeeds_when_all_hashes_match(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('test_entity', 'ok', ['x' => 1]);

        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        $this->assertSame(0, $exit);
    }

    #[Test]
    public function verify_command_fails_when_row_tampered(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('test_entity', 'before_tamper', ['x' => 1]);

        $entry = Activity::latest()->first();
        $this->assertNotNull($entry);

        DB::table('activity_log')->where('id', $entry->id)->update(['description' => 'tampered']);

        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        $this->assertSame(1, $exit);
    }
}
