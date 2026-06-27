<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Custom Activity model that extends Spatie's.
 *
 * FR-13.2: Every audit log entry carries an HMAC-SHA256 hash of its content
 * AND is linked to the previous entry's hash, forming a tamper-evident chain.
 *
 * Chain design:
 *   previous_hash(row 0) = GENESIS_HASH (64 zero hex chars)
 *   sha256_hash(row N)   = HMAC_SHA256(AUDIT_CHAIN_KEY, canonical(fields) + previous_hash)
 *   previous_hash(row N) = sha256_hash(row N-1)
 *
 * This means:
 *   - Modifying any field on row N invalidates sha256_hash on row N.
 *   - Deleting row N causes row N+1's previous_hash to no longer match row N-1's sha256_hash.
 *   - Inserting a fake row requires knowing AUDIT_CHAIN_KEY (not stored in the DB).
 */
class Activity extends SpatieActivity
{
    /**
     * Sentinel value stored as previous_hash on the genesis (first) row.
     * 64 hex zeros — unmistakably not a real HMAC output.
     */
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            // Link this entry to the chain tip. The advisory lock that serializes
            // concurrent appends is held by save() (see below), so this read of the
            // tail row and the subsequent INSERT happen as one critical section.
            //
            // Note: when model events are suppressed (e.g. seeders using
            // WithoutModelEvents) this hook does NOT fire — such callers MUST set
            // previous_hash + sha256_hash manually (see DemoMiscSeeder) or run
            // `php artisan audit:rebuild-chain` afterwards.
            $previous = static::query()->orderByDesc('id')->first();

            $activity->previous_hash = $previous?->sha256_hash ?? static::GENESIS_HASH;
            $activity->sha256_hash   = static::computeHashFor($activity);
        });
    }

    /**
     * Serialize chain appends with a MySQL advisory lock so two concurrent
     * inserts cannot both read the same tail row and fork the chain.
     *
     * The lock is acquired before the INSERT and released in a finally block,
     * so it is ALWAYS released even if the insert throws (no leaked locks).
     * Only new rows (chain appends) take the lock; updates and non-MySQL
     * drivers (e.g. SQLite in tests) fall straight through to the parent.
     *
     * Signature intentionally matches Eloquent\Model::save() (no return type).
     */
    public function save(array $options = [])
    {
        if ($this->exists || ! static::usesAdvisoryLock()) {
            return parent::save($options);
        }

        try {
            static::acquireChainLock();

            return parent::save($options);
        } finally {
            static::releaseChainLock();
        }
    }

    /**
     * Compute HMAC-SHA256 for an Activity row (FR-13.2).
     *
     * Called both at creation time (in the 'creating' hook) and by verification
     * jobs / the audit:verify-activity-hashes command. The algorithm must stay
     * identical in both places — never change the canonical field list or the
     * key derivation without running audit:rebuild-chain afterwards.
     */
    public static function computeHashFor(Activity $activity): string
    {
        $properties = $activity->properties instanceof \Illuminate\Support\Collection
            ? $activity->properties->toArray()
            : Arr::wrap($activity->properties ?? []);

        static::recursiveKsort($properties);

        $data = [
            'previous_hash' => $activity->previous_hash ?? static::GENESIS_HASH,
            'batch_uuid'    => $activity->batch_uuid,
            'causer_id'     => $activity->causer_id,
            'causer_type'   => $activity->causer_type,
            'description'   => $activity->description,
            'event'         => $activity->event,
            'log_name'      => $activity->log_name,
            'properties'    => $properties,
            'subject_id'    => $activity->subject_id,
            'subject_type'  => $activity->subject_type,
        ];

        ksort($data);

        return hash_hmac(
            'sha256',
            json_encode($data, JSON_UNESCAPED_UNICODE),
            static::chainKey()
        );
    }

    /**
     * The HMAC signing key — read from config so it can be set per-environment.
     * Config falls back to APP_KEY when AUDIT_CHAIN_KEY is not in .env.
     */
    public static function chainKey(): string
    {
        return (string) config('audit.chain_key', config('app.key', ''));
    }

    /**
     * Recursively sort an array by its keys so JSON encoding is deterministic
     * regardless of insertion order or DB engine key reordering.
     */
    public static function recursiveKsort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                static::recursiveKsort($value);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Concurrency helpers — advisory lock so two simultaneous inserts do not
    // both read the same tail row and produce a forked chain.
    // -------------------------------------------------------------------------

    /**
     * Advisory locks are only available (and only needed) on MySQL/MariaDB.
     * SQLite (test suite) serializes writes itself, so we skip the lock.
     */
    protected static function usesAdvisoryLock(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    protected static function acquireChainLock(): void
    {
        DB::selectOne("SELECT GET_LOCK('nubl_audit_chain', 5) AS acquired");
    }

    protected static function releaseChainLock(): void
    {
        DB::selectOne("SELECT RELEASE_LOCK('nubl_audit_chain') AS released");
    }
}
