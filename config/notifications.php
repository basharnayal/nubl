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
        'account_approval_pending' => [
            'icon' => 'warning',
            'icon_svg' => 'clock',
            'subtitle' => 'notifications.account_approval_pending_subtitle',
        ],
        'documents_resubmitted_for_review' => [
            'icon' => 'info',
            'icon_svg' => 'users',
            'subtitle' => 'notifications.documents_resubmitted_subtitle',
        ],
        'account_approved' => [
            'icon' => 'success',
            'icon_svg' => 'check-circle',
            'subtitle' => 'Account approval update',
        ],
        'account_rejected' => [
            'icon' => 'warning',
            'icon_svg' => 'clock',
            'subtitle' => 'Account approval update',
        ],
        'request_status_changed' => [
            'icon' => 'info',
            'icon_svg' => 'bell',
            'subtitle' => 'Request status update',
        ],
        'provider_new_request' => [
            'icon' => 'info',
            'icon_svg' => 'bell',
            'subtitle' => 'New request',
        ],
        'provider_request_status_changed' => [
            'icon' => 'info',
            'icon_svg' => 'bell',
            'subtitle' => 'Request status update',
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
