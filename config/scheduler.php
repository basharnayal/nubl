<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduler Test Mode
    |--------------------------------------------------------------------------
    |
    | When true, certain weekly/monthly jobs in routes/console.php are flipped
    | to run hourly so you can verify them quickly. Leave this false in
    | production. The value is read through config() so it survives
    | `php artisan config:cache` (which Forge runs after every deploy).
    |
    | To toggle on a Forge-managed server:
    |   1. Set SCHEDULER_TEST_MODE=true in the site's Environment file.
    |   2. Run `php artisan config:cache` (or trigger a redeploy).
    |
    */

    'test_mode' => (bool) env('SCHEDULER_TEST_MODE', false),

];
