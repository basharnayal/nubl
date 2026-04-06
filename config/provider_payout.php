<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum amount (SAR) to generate a weekly payout request.
    |--------------------------------------------------------------------------
    */

    'min_weekly_payout_amount' => (float) env('PROVIDER_PAYOUT_MIN_AMOUNT', 50),

];
