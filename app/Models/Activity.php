<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Custom Activity model that extends Spatie's.
 * FR-13.2: Automatically computes and stores a SHA-256 hash with each audit log entry.
 */
class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) {
            $activity->sha256_hash = $activity->computeEntryHash();
        });
    }

    /**
     * Compute SHA-256 hash of the audit log entry content (FR-13.2).
     * Delegates to {@see self::computeHashFor} so verification jobs use the same algorithm.
     */
    protected function computeEntryHash(): string
    {
        return static::computeHashFor($this);
    }

    /**
     * Recompute SHA-256 from the model's current attributes (FR-13.2).
     * Used by scheduled integrity checks and tests; must match `creating` payload exactly.
     */
    public static function computeHashFor(Activity $activity): string
    {
        $properties = $activity->properties instanceof \Illuminate\Support\Collection
            ? $activity->properties->toArray()
            : Arr::wrap($activity->properties ?? []);

        static::recursiveKsort($properties);

        $data = [
            'batch_uuid' => $activity->batch_uuid,
            'causer_id' => $activity->causer_id,
            'causer_type' => $activity->causer_type,
            'description' => $activity->description,
            'event' => $activity->event,
            'log_name' => $activity->log_name,
            'properties' => $properties,
            'subject_id' => $activity->subject_id,
            'subject_type' => $activity->subject_type,
        ];

        ksort($data);

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Recursively sort an array by its keys.
     */
    private static function recursiveKsort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                static::recursiveKsort($value);
            }
        }
    }
}
