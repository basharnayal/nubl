<?php

namespace App\Observers;

use App\Http\Services\AuditService;
use App\Models\Request as RequestModel;

/**
 * Central audit for request status transitions (actor + timestamp via Spatie activity).
 */
class RequestObserver
{
    /** @var array<int, string|null> */
    private static array $statusBefore = [];

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function updating(RequestModel $request): void
    {
        if ($request->isDirty('status')) {
            self::$statusBefore[$request->getKey()] = $request->getOriginal('status');
        }
    }

    public function updated(RequestModel $request): void
    {
        if (! $request->wasChanged('status')) {
            return;
        }

        $from = self::$statusBefore[$request->getKey()] ?? null;
        unset(self::$statusBefore[$request->getKey()]);
        $to = $request->status;

        $data = [
            'request_id' => $request->id,
            'recipient_id' => $request->recipient_id,
            'provider_id' => $request->provider_id,
            'from' => $from,
            'to' => $to,
        ];

        if (in_array($to, ['REJECTED', 'ADMIN_REJECTED'], true)) {
            $data['rejection_reason_code'] = $request->rejection_reason_code;
            $data['rejection_reason_note'] = $request->rejection_reason_note;
        }

        if (in_array($to, ['APPROVED', 'REDEEMABLE', 'ADMIN_APPROVED'], true) && $request->funding_source !== null) {
            $data['funding_source'] = $request->funding_source;
        }

        $this->auditService->log('request', 'status_changed', $data, auth()->id());
    }
}
