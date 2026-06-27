<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FR-13.2: Every audit log entry must carry an HMAC-SHA256 hash of its content
 * AND be linked to the previous entry's hash (hash chain).
 *
 * Tests:
 *   1. Hash is auto-populated on creation (not null, 64 hex chars).
 *   2. Stored hash matches re-computation from the same fields.
 *   3. Hash remains stable after a DB round-trip with unordered JSON keys.
 *   4. Tampered content makes the stored hash stale.
 *   5. Genesis row carries GENESIS_HASH as previous_hash.
 *   6. Second row's previous_hash equals first row's sha256_hash (chain link).
 */
class AuditHashIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->auditService = app(AuditService::class);
    }

    // -------------------------------------------------------------------------
    // 1. Hash is auto-populated (not null, correct length)
    // -------------------------------------------------------------------------

    #[Test]
    public function audit_entry_has_sha256_hash_on_creation_fr_13_2(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        $this->auditService->log('test_entity', 'test_action', ['key' => 'value']);

        $entry = Activity::latest()->first();

        $this->assertNotNull($entry, 'Activity entry should exist.');
        $this->assertNotNull($entry->sha256_hash, 'sha256_hash must be set on every audit entry (FR-13.2).');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{64}$/',
            $entry->sha256_hash,
            'sha256_hash must be a 64-character lowercase hex string.'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Stored hash matches re-computation from the same fields
    // -------------------------------------------------------------------------

    #[Test]
    public function stored_hash_matches_recomputed_hash_fr_13_2(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        $this->auditService->log('fund_transaction', 'created', [
            'wallet_id' => 1,
            'direction' => 'IN',
            'amount'    => 100.00,
        ]);

        $entry      = Activity::latest()->first();
        $recomputed = Activity::computeHashFor($entry);

        $this->assertSame(
            $entry->sha256_hash,
            $recomputed,
            'Stored hash must match the recomputed hash from the same fields.'
        );
    }

    // -------------------------------------------------------------------------
    // 3. Hash stable after DB round-trip with unordered properties
    // -------------------------------------------------------------------------

    #[Test]
    public function hash_stable_after_db_round_trip_with_unordered_properties(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        // Keys intentionally NOT in alphabetical order
        $this->auditService->log('auth_test', 'login_test', [
            'z_last'  => 'value',
            'a_first' => 'value',
            'nested'  => ['beta' => 2, 'alpha' => 1],
        ]);

        $entry      = Activity::latest()->first();
        $storedHash = $entry->sha256_hash;

        // Reload from DB — JSON key order may differ
        $entry->refresh();

        $recomputed = Activity::computeHashFor($entry);

        $this->assertSame(
            $storedHash,
            $recomputed,
            'Hash must be stable after a DB round-trip regardless of JSON key order.'
        );
    }

    // -------------------------------------------------------------------------
    // 4. Tamper detection — modifying a field invalidates the stored hash
    // -------------------------------------------------------------------------

    #[Test]
    public function tampered_entry_hash_does_not_match_recomputed_fr_13_2(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        $this->auditService->log('payment', 'succeeded', [
            'payment_id' => 42,
            'amount'     => 500.00,
        ]);

        $entry         = Activity::latest()->first();
        $originalHash  = $entry->sha256_hash;

        // Simulate tampering: change description directly in DB (bypasses model hook)
        DB::table('activity_log')
            ->where('id', $entry->id)
            ->update(['description' => 'payment.tampered']);

        $entry->refresh();

        $recomputedAfterTamper = Activity::computeHashFor($entry);

        $this->assertNotSame(
            $originalHash,
            $recomputedAfterTamper,
            'After tampering, the stored hash must NOT match the recomputed hash (FR-13.2 tamper evidence).'
        );
    }

    // -------------------------------------------------------------------------
    // 5. Genesis row — first entry uses GENESIS_HASH as previous_hash
    // -------------------------------------------------------------------------

    #[Test]
    public function first_audit_entry_uses_genesis_hash_as_previous_hash(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        $this->auditService->log('chain', 'genesis_test', []);

        $entry = Activity::orderBy('id')->first();

        $this->assertNotNull($entry);
        $this->assertSame(
            Activity::GENESIS_HASH,
            $entry->previous_hash,
            'The first audit row must use GENESIS_HASH as previous_hash.'
        );
    }

    // -------------------------------------------------------------------------
    // 6. Chain link — second row's previous_hash equals first row's sha256_hash
    // -------------------------------------------------------------------------

    #[Test]
    public function second_entry_previous_hash_equals_first_entry_sha256_hash(): void
    {
        $user = User::factory()->create(['status' => 'active', 'is_active' => true]);
        $this->actingAs($user);

        $this->auditService->log('chain', 'link_test_row_1', ['seq' => 1]);
        $this->auditService->log('chain', 'link_test_row_2', ['seq' => 2]);

        [$row1, $row2] = Activity::orderBy('id')->take(2)->get()->all();

        $this->assertSame(
            $row1->sha256_hash,
            $row2->previous_hash,
            'Row 2 previous_hash must equal row 1 sha256_hash (chain link is intact).'
        );
    }
}
