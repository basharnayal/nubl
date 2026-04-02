<?php

namespace App\Main;

class SidebarPanel
{
    public static function admin(): array
    {
        return [
            'title' => __('Admin'),
            'items' => [
                [
                    'admin_dashboard' => [
                        'title' => __('Dashboard'),
                        'route_name' => 'admin.dashboard',
                    ],
                    'admin_users' => [
                        'title' => __('Users'),
                        'submenu' => [
                            'admin_users_pending' => [
                                'title' => __('Pending Approvals'),
                                'route_name' => 'admin.users.pending',
                            ],
                            'admin_users_manage' => [
                                'title' => __('User Management'),
                                'route_name' => 'admin.manage.users.index',
                            ],
                        ],
                    ],
                    'admin_requests' => [
                        'title' => __('Requests'),
                        'route_name' => 'admin.requests.index',
                    ],
                    'admin_finances' => [
                        'title' => __('finance.nav.module'),
                        'submenu' => [
                            'admin_finances_overview' => [
                                'title' => __('finance.nav.overview'),
                                'route_name' => 'admin.finances.overview',
                            ],
                            'admin_finances_payments' => [
                                'title' => __('finance.nav.payments'),
                                'route_name' => 'admin.finances.payments.index',
                            ],
                            'admin_finances_ledger' => [
                                'title' => __('finance.nav.fund_ledger'),
                                'route_name' => 'admin.finances.fund-transactions.index',
                            ],
                            'admin_finances_reports' => [
                                'title' => __('finance.nav.reports'),
                                'route_name' => 'admin.finances.reports.index',
                            ],
                            'admin_finances_summary_reports' => [
                                'title' => __('finance.nav.summary_reports'),
                                'route_name' => 'admin.finances.summary-reports.index',
                            ],
                        ],
                    ],
                    'admin_settings' => [
                        'title' => __('Settings'),
                        'submenu' => [
                            'admin_settings_qr' => [
                                'title' => __('QR code validity'),
                                'route_name' => 'admin.settings.qr.edit',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function provider(): array
    {
        return [
            'title' => __('Provider'),
            'items' => [
                [
                    'provider_dashboard' => [
                        'title' => __('Dashboard'),
                        'route_name' => 'provider.dashboard',
                    ],
                    'provider_requests' => [
                        'title' => __('Fulfillments'),
                        'route_name' => 'provider.requests.index',
                    ],
                    'provider_qr' => [
                        'title' => __('Scan QR Code'),
                        'route_name' => 'provider.qr.scan',
                    ],
                    'provider_menu' => [
                        'title' => __('Inventory'),
                        'route_name' => 'provider.menu-items.index',
                    ],
                    'provider_profile_edit' => [
                        'title' => __('provider.sidebar.hours_notes'),
                        'route_name' => 'provider.profile.edit',
                    ],
                    'provider_analytics' => [
                        'title' => __('Analytics'),
                        'route_name' => '',
                    ],
                ],
            ],
        ];
    }

    public static function recipient(): array
    {
        return [
            'title' => __('Recipient'),
            'items' => [
                [
                    'recipient_dashboard' => [
                        'title' => __('Dashboard'),
                        'route_name' => 'recipient.dashboard',
                    ],
                    'recipient_providers' => [
                        'title' => __('Available providers'),
                        'route_name' => 'recipient.providers.index',
                    ],
                    'recipient_requests' => [
                        'title' => __('My Requests'),
                        'route_name' => 'recipient.requests.index',
                    ],
                    'recipient_qr' => [
                        'title' => __('QR Codes'),
                        'route_name' => '',
                    ],
                ],
            ],
        ];
    }

    public static function donor(): array
    {
        return [
            'title' => __('Donor'),
            'items' => [
                [
                    'donor_dashboard' => [
                        'title' => __('Dashboard'),
                        'route_name' => 'donor.dashboard',
                    ],
                    'donor_donations_new' => [
                        'title' => __('New Donation'),
                        'route_name' => 'donor.donations.new',
                    ],
                    'donor_donations_history' => [
                        'title' => __('My Donations'),
                        'route_name' => 'donor.donations.index',
                    ],
                    'donor_statistics' => [
                        'title' => __('Statistics'),
                        'route_name' => '',
                    ],
                ],
            ],
        ];
    }

    public static function forActor(?string $actor): array
    {
        return match ($actor) {
            'admin' => self::admin(),
            'provider' => self::provider(),
            'recipient' => self::recipient(),
            'donor' => self::donor(),
            default => self::admin(),
        };
    }
}
