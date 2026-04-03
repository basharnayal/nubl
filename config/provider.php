<?php

return [
    'cities' => [
        'medina' => 'المدينة المنورة', // Medina (primary for now)
        // Add more cities later: 'riyadh' => 'الرياض', etc.
    ],

    'recipient' => [
        'weekly_allowance_limit' => 400,
        /** FR-17.1: bounds for admin-configured weekly limit (SAR) */
        'weekly_allowance_limit_min' => 1,
        'weekly_allowance_limit_max' => 100_000,
        /** FR-6.4: delayed job retries submit when weekly allowance was temporarily exceeded */
        'allowance_retry_delay_seconds' => 60,
        /** FR-6.4: delayed job retries submit when city fund (payment pool) is temporarily insufficient */
        'fund_retry_delay_seconds' => 60,
    ],

    'regions' => [
        'western' => 'المنطقة الغربية', // Western Region (fixed)
    ],

    'adoption_support_options' => [
        'yes' => 'Yes',      // نعم
        'partially' => 'Partially',  // جزئيًا
        'no' => 'No',        // لا
    ],

    'business_categories' => [
        'restaurant',
        'catering',
        'bakery',
        'grocery',
        'food_truck',
        'other',
    ],

    'service_types' => [
        'meal_preparation',
        'delivery',
        'pickup',
        'both_delivery_pickup',
    ],

    'document_max_size_mb' => 5,

    'weekdays' => [
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
    ],
];
