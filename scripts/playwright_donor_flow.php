<?php

declare(strict_types=1);

use App\Models\OrderProof;
use App\Models\OrderRedemption;
use App\Models\Payment;
use App\Models\ProviderMenuItem;
use App\Models\Request;
use App\Models\RequestPaymentLink;
use App\Models\User;
use App\Support\PseudonymousRequestId;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function uniquePhone(string $seed, string $pad): string
{
    return '05' . str_pad(substr($seed, -8), 8, $pad, STR_PAD_LEFT);
}

function donorPayload(?User $user): ?array
{
    if (! $user) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
}

$action = $argv[1] ?? null;

if ($action === 'seed') {
    $suffix = $argv[2] ?? (string) time();
    $password = 'password123';

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

    $primaryDonor = User::create([
        'name' => "Playwright Donor {$suffix}",
        'email' => "pw-donor-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_DONOR,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '1', '6'),
        'phone_verified_at' => now(),
    ]);
    $primaryDonor->forceFill(['email_verified_at' => now()])->save();
    $primaryDonor->assignRole('donor');

    $emptyDonor = User::create([
        'name' => "Empty Donor {$suffix}",
        'email' => "pw-empty-donor-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_DONOR,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '2', '5'),
        'phone_verified_at' => now(),
    ]);
    $emptyDonor->forceFill(['email_verified_at' => now()])->save();
    $emptyDonor->assignRole('donor');

    $otherDonor = User::create([
        'name' => "Other Donor {$suffix}",
        'email' => "pw-other-donor-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_DONOR,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '3', '4'),
        'phone_verified_at' => now(),
    ]);
    $otherDonor->forceFill(['email_verified_at' => now()])->save();
    $otherDonor->assignRole('donor');

    $provider = User::create([
        'name' => "Donor Provider {$suffix}",
        'email' => "pw-donor-provider-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_PROVIDER,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'accepting_orders' => true,
        'phone_number' => uniquePhone($suffix . '4', '3'),
        'phone_verified_at' => now(),
    ]);
    $provider->forceFill(['email_verified_at' => now()])->save();
    $provider->assignRole('provider');

    $recipient = User::create([
        'name' => "Hidden Recipient {$suffix}",
        'email' => "pw-donor-recipient-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_RECIPIENT,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => uniquePhone($suffix . '5', '2'),
        'phone_verified_at' => now(),
    ]);
    $recipient->forceFill(['email_verified_at' => now()])->save();
    $recipient->assignRole('recipient');

    $menuItem = ProviderMenuItem::create([
        'provider_id' => $provider->id,
        'name' => "Impact Meal {$suffix}",
        'description' => 'Seeded for Playwright donor coverage',
        'price' => 70.00,
        'category' => 'Meals',
        'is_active' => true,
    ]);

    $succeededPaymentOne = Payment::create([
        'sponsor_id' => $primaryDonor->id,
        'gateway' => Payment::GATEWAY_MYFATOORAH,
        'external_payment_id' => "DONOR-SUCCESS-1-{$suffix}",
        'status' => Payment::STATUS_SUCCEEDED,
        'amount' => 120.50,
        'notes' => ['seeded' => true],
        'idempotency_key' => "seed-success-1-{$suffix}",
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    $succeededPaymentTwo = Payment::create([
        'sponsor_id' => $primaryDonor->id,
        'gateway' => Payment::GATEWAY_MYFATOORAH,
        'external_payment_id' => "DONOR-SUCCESS-2-{$suffix}",
        'status' => Payment::STATUS_SUCCEEDED,
        'amount' => 79.50,
        'notes' => ['seeded' => true],
        'idempotency_key' => "seed-success-2-{$suffix}",
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $failedPayment = Payment::create([
        'sponsor_id' => $primaryDonor->id,
        'gateway' => Payment::GATEWAY_MYFATOORAH,
        'external_payment_id' => "DONOR-FAILED-{$suffix}",
        'status' => Payment::STATUS_FAILED,
        'amount' => 15.00,
        'notes' => ['seeded' => true],
        'idempotency_key' => "seed-failed-{$suffix}",
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $pendingPayment = Payment::create([
        'sponsor_id' => $primaryDonor->id,
        'gateway' => Payment::GATEWAY_MYFATOORAH,
        'external_payment_id' => "DONOR-PENDING-{$suffix}",
        'status' => Payment::STATUS_PENDING,
        'amount' => 55.25,
        'notes' => ['seeded' => true],
        'idempotency_key' => "seed-pending-{$suffix}",
        'created_at' => now()->subHours(12),
        'updated_at' => now()->subHours(12),
    ]);

    $otherDonorPayment = Payment::create([
        'sponsor_id' => $otherDonor->id,
        'gateway' => Payment::GATEWAY_MYFATOORAH,
        'external_payment_id' => "OTHER-SUCCESS-{$suffix}",
        'status' => Payment::STATUS_SUCCEEDED,
        'amount' => 999.99,
        'notes' => ['seeded' => true],
        'idempotency_key' => "seed-other-success-{$suffix}",
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $fulfilledRequest = Request::create([
        'recipient_id' => $recipient->id,
        'provider_id' => $provider->id,
        'reserved_amount' => 70.00,
        'status' => 'FULFILLED',
        'funding_source' => 'CITY_FUND',
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);
    $fulfilledRequest->items()->create([
        'menu_item_id' => $menuItem->id,
        'quantity' => 1,
        'price_snapshot' => 70.00,
    ]);

    $fulfilledRedemption = OrderRedemption::create([
        'request_id' => $fulfilledRequest->id,
        'provider_id' => $provider->id,
        'token_code' => "token-{$suffix}",
        'short_code_hash' => hash('sha256', "short-{$suffix}"),
        'token_ciphertext' => "cipher-{$suffix}",
        'short_code_ciphertext' => "short-cipher-{$suffix}",
        'token_qr_url' => 'https://example.com/qr',
        'ttl_minutes' => 60,
        'redeem_expires_at' => now()->addHour(),
        'status' => 'REDEEMED',
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    OrderProof::create([
        'order_redemption_id' => $fulfilledRedemption->id,
        'proof_url' => 'proofs/playwright-donor-proof.jpg',
        'is_provider_donation' => false,
        'fulfilled_at' => now()->subDays(3),
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    $redeemableRequest = Request::create([
        'recipient_id' => $recipient->id,
        'provider_id' => $provider->id,
        'reserved_amount' => 50.00,
        'status' => 'REDEEMABLE',
        'funding_source' => 'CITY_FUND',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);
    $redeemableRequest->items()->create([
        'menu_item_id' => $menuItem->id,
        'quantity' => 1,
        'price_snapshot' => 50.00,
    ]);

    RequestPaymentLink::create([
        'payment_id' => $succeededPaymentOne->id,
        'request_id' => $fulfilledRequest->id,
        'amount' => 70.00,
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ]);

    RequestPaymentLink::create([
        'payment_id' => $succeededPaymentOne->id,
        'request_id' => $redeemableRequest->id,
        'amount' => 50.00,
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    echo json_encode([
        'password' => $password,
        'primaryDonor' => donorPayload($primaryDonor),
        'emptyDonor' => donorPayload($emptyDonor),
        'otherDonor' => donorPayload($otherDonor),
        'providerName' => $provider->name,
        'recipientName' => $recipient->name,
        'payments' => [
            'succeededOneId' => $succeededPaymentOne->id,
            'succeededTwoId' => $succeededPaymentTwo->id,
            'failedId' => $failedPayment->id,
            'pendingId' => $pendingPayment->id,
            'otherDonorSucceededId' => $otherDonorPayment->id,
        ],
        'impact' => [
            'totalDonated' => '200.00',
            'donationCount' => 2,
            'requestsFunded' => 2,
            'requestsDelivered' => 2,
            'amountAllocated' => '120.00',
            'fulfilledReference' => PseudonymousRequestId::make($fulfilledRequest->id),
            'redeemableReference' => PseudonymousRequestId::make($redeemableRequest->id),
        ],
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'payment') {
    $payment = Payment::find((int) ($argv[2] ?? 0));

    echo json_encode($payment ? [
        'id' => $payment->id,
        'status' => $payment->status,
        'amount' => number_format((float) $payment->amount, 2, '.', ''),
        'sponsor_id' => $payment->sponsor_id,
    ] : null, JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'payment-count') {
    $sponsorId = (int) ($argv[2] ?? 0);
    echo json_encode([
        'sponsor_id' => $sponsorId,
        'count' => Payment::where('sponsor_id', $sponsorId)->count(),
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
