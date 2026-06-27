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

/**
 * Tests for the audit:verify-activity-hashes command.
 *
 * Covers:
 *   1. Clean chain passes verification.
 *   2. Content tamper (field edit) causes failure.
 *   3. Chain break (row deleted) causes failure.
 *   4. Chain break (previous_hash forced wrong) causes failure.
 *   5. Null sha256_hash rows are skipped with a warning, not a failure.
 */
class VerifyActivityLogIntegrityCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // 1. Clean chain — command exits 0
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_command_succeeds_when_all_hashes_and_chain_links_match(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('test_entity', 'ok_row1', ['x' => 1]);
        app(AuditService::class)->log('test_entity', 'ok_row2', ['x' => 2]);

        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        $this->assertSame(0, $exit);
    }

    // -------------------------------------------------------------------------
    // 2. Content tamper — modifying a field exits 1
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_command_fails_when_row_content_is_tampered(): void
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

    // -------------------------------------------------------------------------
    // 3. Chain break via deleted middle row — exits 1
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_command_fails_when_a_row_is_deleted_from_the_middle(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('chain', 'row1', ['seq' => 1]);
        app(AuditService::class)->log('chain', 'row2', ['seq' => 2]);
        app(AuditService::class)->log('chain', 'row3', ['seq' => 3]);

        // Grab the middle row's id and delete it — simulates a record being erased
        $middleId = Activity::orderBy('id')->skip(1)->value('id');
        DB::table('activity_log')->where('id', $middleId)->delete();

        // Row3's previous_hash now points to deleted row2, so it won't match row1
        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        $this->assertSame(1, $exit);
    }

    // -------------------------------------------------------------------------
    // 4. Chain break via corrupted previous_hash — exits 1
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_command_fails_when_previous_hash_is_wrong(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('chain', 'anchor', ['seq' => 1]);
        app(AuditService::class)->log('chain', 'linked', ['seq' => 2]);

        $second = Activity::orderBy('id')->skip(1)->first();

        // Forge a wrong previous_hash on the second row
        DB::table('activity_log')
            ->where('id', $second->id)
            ->update(['previous_hash' => str_repeat('a', 64)]);

        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        $this->assertSame(1, $exit);
    }

    // -------------------------------------------------------------------------
    // 5. Null sha256_hash rows are warned but do not cause failure
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_command_warns_about_null_hash_rows_but_does_not_fail(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        app(AuditService::class)->log('test_entity', 'normal_row', []);

        // Force sha256_hash to null to simulate a legacy pre-migration row
        DB::table('activity_log')->update(['sha256_hash' => null]);

        $exit = Artisan::call('audit:verify-activity-hashes', ['--chunk' => 50]);

        // Null rows are skipped, not treated as failures
        $this->assertSame(0, $exit);
    }
}
