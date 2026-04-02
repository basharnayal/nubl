<?php

declare(strict_types=1);

use App\Models\Ewallet;
use App\Models\FundTransaction;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\ProviderProfile;
use App\Models\Request;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function uniquePhone(string $seed, string $pad): string
{
    return '05' . str_pad(substr($seed, -8), 8, $pad, STR_PAD_LEFT);
}

function userPayload(?User $user): ?array
{
    if (!$user) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'status' => $user->status,
        'is_active' => (bool) $user->is_active,
    ];
}

$action = $argv[1] ?? null;

if ($action === 'seed') {
    $suffix = $argv[2] ?? (string) time();
    $password = 'password123';

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

    $qrPermission = Permission::firstOrCreate(['name' => 'qr.configure_ttl', 'guard_name' => 'web']);
    if (!$adminRole->hasPermissionTo($qrPermission)) {
        $adminRole->givePermissionTo($qrPermission);
    }

    $admin = User::create([
        'name' => "Playwright Admin {$suffix}",
        'email' => "pw-admin-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => 'admin',
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix, '7'),
        'phone_verified_at' => now(),
    ]);
    $admin->forceFill(['email_verified_at' => now()])->save();
    $admin->assignRole('admin');

    $pendingProvider = User::create([
        'name' => "Pending Provider {$suffix}",
        'email' => "pw-pending-provider-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_PROVIDER,
        'status' => User::STATUS_PENDING_APPROVAL,
        'is_active' => true,
        'accepting_orders' => true,
        'phone_number' => uniquePhone($suffix . '1', '6'),
    ]);
    $pendingProvider->assignRole('provider');
    ProviderProfile::create([
        'user_id' => $pendingProvider->id,
        'full_name_ar' => 'Pending Provider',
        'full_name_en' => 'Pending Provider',
        'phone_number' => uniquePhone($suffix . '1', '6'),
        'email' => $pendingProvider->email,
        'business_name_ar' => "Pending Provider {$suffix}",
        'business_name_en' => "Pending Provider {$suffix}",
        'unified_number' => '7000000001',
        'business_category' => ['Food'],
        'address_ar' => 'Riyadh',
        'address_en' => 'Riyadh',
        'city' => 'Riyadh',
        'region' => 'Riyadh',
        'location' => 'Center',
    ]);

    $pendingRecipient = User::create([
        'name' => "Pending Recipient {$suffix}",
        'email' => "pw-pending-recipient-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_RECIPIENT,
        'status' => User::STATUS_PENDING_APPROVAL,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '2', '5'),
    ]);
    $pendingRecipient->assignRole('recipient');

    $managedDonor = User::create([
        'name' => "Managed Donor {$suffix}",
        'email' => "pw-managed-donor-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_DONOR,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '3', '4'),
        'phone_verified_at' => now(),
    ]);
    $managedDonor->forceFill(['email_verified_at' => now()])->save();
    $managedDonor->assignRole('donor');

    $provider = User::create([
        'name' => "Request Provider {$suffix}",
        'email' => "pw-request-provider-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_PROVIDER,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'accepting_orders' => true,
        'phone_number' => uniquePhone($suffix . '4', '3'),
    ]);
    $provider->assignRole('provider');

    $recipient = User::create([
        'name' => "Request Recipient {$suffix}",
        'email' => "pw-request-recipient-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_RECIPIENT,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '5', '2'),
    ]);
    $recipient->assignRole('recipient');

    $item = ProviderMenuItem::create([
        'provider_id' => $provider->id,
        'name' => "Admin Queue Item {$suffix}",
        'price' => 100,
        'is_active' => true,
    ]);

    $approveRequest = Request::create([
        'recipient_id' => $recipient->id,
        'provider_id' => $provider->id,
        'reserved_amount' => 100,
        'status' => 'ADMIN_PENDING',
        'funding_source' => 'CITY_FUND',
    ]);
    $approveRequest->items()->create([
        'menu_item_id' => $item->id,
        'quantity' => 1,
        'price_snapshot' => 100,
    ]);

    $rejectRequest = Request::create([
        'recipient_id' => $recipient->id,
        'provider_id' => $provider->id,
        'reserved_amount' => 200,
        'status' => 'ADMIN_PENDING',
        'funding_source' => 'CITY_FUND',
    ]);
    $rejectRequest->items()->create([
        'menu_item_id' => $item->id,
        'quantity' => 2,
        'price_snapshot' => 100,
    ]);

    $systemWallet = Ewallet::create([
        'owner_type' => 'SYSTEM',
        'owner_id' => null,
        'balance' => 0,
        'status' => true,
    ]);

    $payment = Payment::factory()->for($managedDonor, 'sponsor')->succeeded()->create([
        'amount' => 75,
        'external_payment_id' => "INV-{$suffix}",
    ]);

    $fundTransaction = FundTransaction::create([
        'wallet_id' => $systemWallet->id,
        'sponsor_id' => $managedDonor->id,
        'source' => FundTransaction::SOURCE_DONATION,
        'amount' => 75,
        'direction' => FundTransaction::DIRECTION_IN,
        'payment_id' => $payment->id,
        'request_id' => null,
        'order_redemption_id' => null,
    ]);

    echo json_encode([
        'adminEmail' => $admin->email,
        'password' => $password,
        'pendingProviderId' => $pendingProvider->id,
        'pendingProviderEmail' => $pendingProvider->email,
        'pendingRecipientId' => $pendingRecipient->id,
        'pendingRecipientEmail' => $pendingRecipient->email,
        'managedDonorId' => $managedDonor->id,
        'managedDonorEmail' => $managedDonor->email,
        'approveRequestId' => $approveRequest->id,
        'rejectRequestId' => $rejectRequest->id,
        'paymentId' => $payment->id,
        'paymentExternalId' => $payment->external_payment_id,
        'fundTransactionId' => $fundTransaction->id,
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'user') {
    echo json_encode(userPayload(User::find((int) ($argv[2] ?? 0))), JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'user-by-email') {
    echo json_encode(userPayload(User::where('email', $argv[2] ?? '')->first()), JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'request') {
    $request = Request::find((int) ($argv[2] ?? 0));
    echo json_encode($request ? [
        'id' => $request->id,
        'status' => $request->status,
        'rejection_reason_code' => $request->rejection_reason_code,
        'rejection_reason_note' => $request->rejection_reason_note,
    ] : null, JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'setting') {
    $key = $argv[2] ?? '';
    echo json_encode(['key' => $key, 'value' => \App\Models\SystemSetting::getValue($key)], JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
