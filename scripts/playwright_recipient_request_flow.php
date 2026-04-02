<?php

declare(strict_types=1);

use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\Request;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$action = $argv[1] ?? null;

if ($action === 'seed') {
    $uniqueSuffix = $argv[2] ?? (string) time();
    $recipientEmail = "pw-recipient-{$uniqueSuffix}@example.com";
    $providerEmail = "pw-provider-{$uniqueSuffix}@example.com";
    $password = 'password123';
    $businessName = "PW Provider {$uniqueSuffix}";
    $itemName = "PW Kabsa {$uniqueSuffix}";
    $recipientPhone = '05' . str_pad(substr(strrev($uniqueSuffix), 0, 8), 8, '1', STR_PAD_RIGHT);
    $providerPhone = '05' . str_pad(substr($uniqueSuffix, -8), 8, '0', STR_PAD_LEFT);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

    $recipient = User::create([
        'name' => 'Playwright Recipient',
        'email' => $recipientEmail,
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_RECIPIENT,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => $recipientPhone,
        'phone_verified_at' => now(),
    ]);
    $recipient->forceFill(['email_verified_at' => now()])->save();
    $recipient->assignRole('recipient');

    $provider = User::create([
        'name' => 'Playwright Provider',
        'email' => $providerEmail,
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_PROVIDER,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'accepting_orders' => true,
        'phone_number' => $providerPhone,
        'phone_verified_at' => now(),
    ]);
    $provider->forceFill(['email_verified_at' => now()])->save();
    $provider->assignRole('provider');

    ProviderProfile::create([
        'user_id' => $provider->id,
        'full_name_ar' => 'Playwright Provider',
        'full_name_en' => 'Playwright Provider',
        'phone_number' => $providerPhone,
        'email' => $providerEmail,
        'business_name_ar' => $businessName,
        'business_name_en' => $businessName,
        'unified_number' => '7000000000',
        'business_category' => ['Food'],
        'address_ar' => 'Riyadh',
        'address_en' => 'Riyadh',
        'city' => 'Riyadh',
        'region' => 'Riyadh',
        'location' => 'Downtown',
    ]);

    ProviderOperatingInfo::create([
        'user_id' => $provider->id,
        'daily_capacity' => 50,
        'operating_hours' => [],
        'service_type' => ['delivery'],
        'estimated_preparation_order_time' => '30 mins',
    ]);

    $item = ProviderMenuItem::create([
        'provider_id' => $provider->id,
        'name' => $itemName,
        'description' => 'Seeded for Playwright',
        'price' => 45.00,
        'category' => 'Meals',
        'is_active' => true,
    ]);

    echo json_encode([
        'recipientEmail' => $recipientEmail,
        'password' => $password,
        'providerId' => $provider->id,
        'itemId' => $item->id,
        'businessName' => $businessName,
        'itemName' => $itemName,
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'fetch') {
    $itemId = (int) ($argv[2] ?? 0);

    $request = Request::query()
        ->whereHas('items', fn ($query) => $query->where('menu_item_id', $itemId))
        ->latest('id')
        ->first();

    echo json_encode($request ? [
        'id' => $request->id,
        'status' => $request->status,
        'reserved_amount' => (float) $request->reserved_amount,
    ] : null, JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
