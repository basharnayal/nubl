<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FR-13.2: Every audit log entry must carry a SHA-256 hash of its content.
 * Tests:
 *   1. Hash is populated on creation (not null, 64 hex chars).
 *   2. Hash is stable — re-computing from the stored row matches the stored value.
 *   3. Tamper detection — mutating a stored field invalidates the hash.
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

        $this->auditService->log('test_entity', 'test_action', [
            'key' => 'value',
        ]);

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
    // 2. Hash integrity — re-computed hash matches stored hash
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

        $entry = Activity::latest()->first();

        // Re-compute the same way Activity::computeEntryHash does
        $data = [
            'batch_uuid'   => $entry->batch_uuid,
            'causer_id'    => $entry->causer_id,
            'causer_type'  => $entry->causer_type,
            'description'  => $entry->description,
            'event'        => $entry->event,
            'log_name'     => $entry->log_name,
            'properties'   => $entry->properties instanceof \Illuminate\Support\Collection
                ? $entry->properties->toArray()
                : Arr::wrap($entry->properties ?? []),
            'subject_id'   => $entry->subject_id,
            'subject_type' => $entry->subject_type,
        ];
        ksort($data);

        $recomputed = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));

        $this->assertSame(
            $entry->sha256_hash,
            $recomputed,
            'Stored SHA-256 hash must match the recomputed hash from the same fields (tamper-evident integrity check).'
        );
    }

    // -------------------------------------------------------------------------
    // 3. Tamper detection — modifying a field makes stored hash stale
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

        $entry = Activity::latest()->first();
        $originalHash = $entry->sha256_hash;

        // Simulate tampering: change description in DB directly (bypasses the model hook)
        \Illuminate\Support\Facades\DB::table('activity_log')
            ->where('id', $entry->id)
            ->update(['description' => 'payment.tampered']);

        $entry->refresh();

        // Re-compute with the tampered description
        $data = [
            'batch_uuid'   => $entry->batch_uuid,
            'causer_id'    => $entry->causer_id,
            'causer_type'  => $entry->causer_type,
            'description'  => $entry->description,  // now 'payment.tampered'
            'event'        => $entry->event,
            'log_name'     => $entry->log_name,
            'properties'   => $entry->properties instanceof \Illuminate\Support\Collection
                ? $entry->properties->toArray()
                : Arr::wrap($entry->properties ?? []),
            'subject_id'   => $entry->subject_id,
            'subject_type' => $entry->subject_type,
        ];
        ksort($data);

        $recomputedAfterTamper = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));

        $this->assertNotSame(
            $originalHash,
            $recomputedAfterTamper,
            'After tampering, the original stored hash should NOT match the recomputed hash — this proves tamper-evidence (FR-13.2).'
        );
    }
}
