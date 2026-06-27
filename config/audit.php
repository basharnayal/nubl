<?php

return [
    /*
     * HMAC key used to sign every audit log entry hash (FR-13.2 hash chain).
     *
     * IMPORTANT: This key is the root of trust for the entire audit chain.
     * - Generate with: php artisan key:generate --show  (then pick a different value)
     * - Store it in .env as AUDIT_CHAIN_KEY (64+ random characters recommended).
     * - Losing this key makes historical chain verification impossible.
     * - Never commit the real value to version control.
     *
     * Falls back to APP_KEY so the system degrades gracefully in dev setups that
     * have not yet added AUDIT_CHAIN_KEY to .env.
     */
    'chain_key' => env('AUDIT_CHAIN_KEY', env('APP_KEY', '')),
];
