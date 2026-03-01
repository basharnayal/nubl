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
     */
    protected function computeEntryHash(): string
    {
        $data = [
            'batch_uuid' => $this->batch_uuid,
            'causer_id' => $this->causer_id,
            'causer_type' => $this->causer_type,
            'description' => $this->description,
            'event' => $this->event,
            'log_name' => $this->log_name,
            'properties' => $this->properties instanceof \Illuminate\Support\Collection
                ? $this->properties->toArray()
                : Arr::wrap($this->properties ?? []),
            'subject_id' => $this->subject_id,
            'subject_type' => $this->subject_type,
        ];

        ksort($data);

        return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
