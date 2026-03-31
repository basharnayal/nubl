<?php

namespace App\Support;

/**
 * FR-25.2: Deterministic pseudonymous request reference (no PII).
 * Same algorithm as donor dashboard — must stay in sync everywhere.
 */
final class PseudonymousRequestId
{
    public static function make(int $requestId): string
    {
        return 'R-'.strtoupper(substr(hash('sha256', 'req_'.$requestId.config('app.key')), 0, 8));
    }
}
