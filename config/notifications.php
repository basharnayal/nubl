<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Types (Display Config)
    |--------------------------------------------------------------------------
    |
    | Register notification types for display in the UI.
    | When adding a new notification: create the Notification class, then add
    | its type here. No need to edit NotificationController.
    |
    | Keys: type name (must match 'type' in notification's toArray())
    | Values: icon, icon_svg, subtitle (optional - can override in toArray)
    |
    */

    'types' => [
        'donation_receipt' => [
            'icon' => 'success',
            'icon_svg' => 'check-circle',
            'subtitle' => 'Receipt has been sent to your email',
        ],
        'new_user_registered' => [
            'icon' => 'info',
            'icon_svg' => 'users',
            'subtitle' => 'New user registration',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default (fallback for unknown types)
    |--------------------------------------------------------------------------
    */

    'default' => [
        'icon' => 'info',
        'icon_svg' => 'bell',
        'subtitle' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Icons Reference
    |--------------------------------------------------------------------------
    |
    | icon: success | warning | info | primary
    | icon_svg: check-circle | bell | clock | users
    |
    */

];
