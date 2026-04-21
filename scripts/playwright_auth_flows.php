<?php

declare(strict_types=1);

use App\Support\PhoneHelper;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
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

function ensureRoles(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
}

function userPayload(?User $user): ?array
{
    if (! $user) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'membership_type' => $user->membership_type,
        'status' => $user->status,
        'phone_number' => $user->phone_number,
        'hasRecipientProfile' => $user->recipientProfile !== null,
        'hasRecipientKycDetails' => $user->recipientKycDetails !== null,
        'hasProviderProfile' => $user->providerProfile !== null,
        'hasProviderOperatingInfo' => $user->providerOperatingInfo !== null,
        'hasProviderFinancialInfo' => $user->providerFinancialInfo !== null,
        'hasProviderDocuments' => $user->providerDocuments !== null,
    ];
}

$action = $argv[1] ?? null;

if ($action === 'ensure-roles') {
    ensureRoles();
    echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'seed-login-users') {
    ensureRoles();

    $suffix = $argv[2] ?? (string) time();
    $password = 'password123';

    $donor = User::create([
        'name' => "Playwright Email Donor {$suffix}",
        'email' => "pw-email-donor-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_DONOR,
        'status' => User::STATUS_ACTIVE,
        'is_active' => true,
        'phone_number' => PhoneHelper::normalize(uniquePhone($suffix . '1', '7')),
        'phone_verified_at' => now(),
    ]);
    $donor->forceFill(['email_verified_at' => now()])->save();
    $donor->assignRole('donor');

    $recipient = User::create([
        'name' => "Playwright Pending Recipient {$suffix}",
        'email' => "pw-email-recipient-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_RECIPIENT,
        'status' => User::STATUS_PENDING_APPROVAL,
        'is_active' => true,
        'phone_number' => PhoneHelper::normalize(uniquePhone($suffix . '2', '6')),
        'phone_verified_at' => now(),
    ]);
    $recipient->forceFill(['email_verified_at' => now()])->save();
    $recipient->assignRole('recipient');

    RecipientProfile::create([
        'user_id' => $recipient->id,
        'nationality' => 'Saudi Arabia',
        'short_address' => 'Playwright Recipient Address',
        'id_type' => 'national_id',
        'id_photo_path' => 'recipient_id_photos/playwright-id.png',
    ]);

    RecipientKycDetails::create([
        'user_id' => $recipient->id,
        'income_band' => '1000-1500',
        'household_size' => 4,
        'marital_status' => 'married',
        'is_student' => false,
        'address_confirmation' => 'recipient_address_photos/playwright-address.png',
    ]);

    $provider = User::create([
        'name' => "Playwright Pending Provider {$suffix}",
        'email' => "pw-email-provider-{$suffix}@example.com",
        'password' => Hash::make($password),
        'membership_type' => User::MEMBERSHIP_PROVIDER,
        'status' => User::STATUS_PENDING_APPROVAL,
        'is_active' => true,
        'accepting_orders' => true,
        'phone_number' => PhoneHelper::normalize(uniquePhone($suffix . '3', '5')),
        'phone_verified_at' => now(),
    ]);
    $provider->forceFill(['email_verified_at' => now()])->save();
    $provider->assignRole('provider');

    ProviderProfile::create([
        'user_id' => $provider->id,
        'full_name_ar' => 'Provider Arabic Name',
        'full_name_en' => 'Playwright Pending Provider',
        'phone_number' => $provider->phone_number,
        'email' => $provider->email,
        'business_name_ar' => 'Provider Business Arabic',
        'business_name_en' => 'Playwright Provider Business',
        'unified_number' => '7000000099',
        'business_category' => ['restaurant'],
        'address_ar' => 'Provider Arabic Address',
        'address_en' => 'Playwright Provider Address',
        'city' => 'medina',
        'region' => 'western',
        'location' => 'Downtown',
    ]);

    ProviderOperatingInfo::create([
        'user_id' => $provider->id,
        'operating_hours' => [
            'sunday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            'monday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            'tuesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            'wednesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            'thursday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
            'friday' => ['closed' => true],
            'saturday' => ['closed' => true],
        ],
        'daily_capacity' => 60,
        'service_type' => ['meal_preparation', 'delivery'],
        'estimated_preparation_order_time' => '30 minutes',
        'adoption_support' => 'yes',
    ]);

    ProviderFinancialInfo::create([
        'user_id' => $provider->id,
        'bank_name' => 'Playwright Bank',
        'iban' => 'SA0380000000608010167519',
        'account_holder_name' => 'Playwright Pending Provider',
    ]);

    ProviderDocuments::create([
        'user_id' => $provider->id,
        'business_license_path' => 'provider_documents/playwright-license.pdf',
        'id_or_iqama_path' => 'provider_documents/playwright-id.pdf',
    ]);

    echo json_encode([
        'password' => $password,
        'donor' => userPayload($donor),
        'recipient' => userPayload($recipient),
        'provider' => userPayload($provider),
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'user-by-email') {
    $user = User::with([
        'recipientProfile',
        'recipientKycDetails',
        'providerProfile',
        'providerOperatingInfo',
        'providerFinancialInfo',
        'providerDocuments',
    ])->where('email', $argv[2] ?? '')->first();

    echo json_encode(userPayload($user), JSON_THROW_ON_ERROR);
    exit(0);
}

if ($action === 'set-login-otp') {
    $phone = PhoneHelper::normalize($argv[2] ?? '');
    $code = preg_replace('/\D/', '', $argv[3] ?? '123456');
    Cache::put('otp:login:' . $phone, $code, now()->addMinutes(5));

    echo json_encode([
        'phone' => $phone,
        'code' => $code,
    ], JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
