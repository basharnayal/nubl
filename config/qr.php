<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default QR redemption window (minutes) — FR-8.2
    |--------------------------------------------------------------------------
    | Used when no admin override exists in system_settings.
    */
    'ttl_minutes' => (int) env('QR_TTL_MINUTES', 180),

    /*
    |--------------------------------------------------------------------------
    | Allowed range for admin-configurable TTL — FR-8.3
    |--------------------------------------------------------------------------
    */
    'ttl_minutes_min' => 15,

    'ttl_minutes_max' => 720,

];
