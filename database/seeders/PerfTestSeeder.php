<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderMenuItem;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * PerfTestSeeder
 * ----------------------------------------------------------------------------
 * Seeds 50 donors + 50 recipients + 20 providers + 1 admin, all pre-approved
 * and ready for the k6 performance test suite (see perf_test_plan.md §13.3).
 *
 * Idempotent — safe to re-run; uses firstOrCreate everywhere.
 *
 * Run with:
 *     php artisan db:seed --class=PerfTestSeeder
 *
 * Emits a CSV at storage/app/perf-test-users.csv that you can copy directly
 * into tests/k6/data/users.csv:
 *     cp storage/app/perf-test-users.csv tests/k6/data/users.csv
 *
 * NOT registered in DatabaseSeeder — run on demand only.
 * ----------------------------------------------------------------------------
 */
class PerfTestSeeder extends Seeder
{
    /** Shared bcrypt hash — reuses one expensive computation for all 121 users. */
    private string $passwordHash;

    /** Plain password — written to CSV so k6 can authenticate. */
    private const PASSWORD = 'PerfTest!2026';

    private const NUM_DONORS = 50;
    private const NUM_RECIPIENTS = 50;
    private const NUM_PROVIDERS = 20;
    private const MENU_ITEMS_PER_PROVIDER = 10;

    public function run(): void
    {
        $this->command->info('PerfTestSeeder: starting…');
        $this->passwordHash = Hash::make(self::PASSWORD);

        // Ensure the four roles exist (matches RoleSeeder + Spatie config).
        $donorRole = Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);
        $recipientRole = Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);
        $providerRole = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $credentials = [];

        DB::transaction(function () use ($donorRole, $recipientRole, $providerRole, $adminRole, &$credentials) {
            $credentials = array_merge(
                $this->seedDonors($donorRole),
                $this->seedRecipients($recipientRole),
                $this->seedProviders($providerRole),
                $this->seedAdmins($adminRole),
            );
        });

        $csvPath = storage_path('app/perf-test-users.csv');
        $this->writeCredentialsCsv($credentials, $csvPath);

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info(' PerfTestSeeder finished');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info(sprintf(
            ' Seeded: %d donors, %d recipients, %d providers, %d admin',
            self::NUM_DONORS,
            self::NUM_RECIPIENTS,
            self::NUM_PROVIDERS,
            1,
        ));
        $this->command->info(sprintf(
            ' Menu items created: %d (~%d per provider)',
            self::NUM_PROVIDERS * self::MENU_ITEMS_PER_PROVIDER,
            self::MENU_ITEMS_PER_PROVIDER,
        ));
        $this->command->info(' Password (all users): '.self::PASSWORD);
        $this->command->info(' CSV written to:       '.$csvPath);
        $this->command->info('');
        $this->command->info(' Next step — copy into the k6 data directory:');
        $this->command->info('   cp '.$csvPath.' tests/k6/data/users.csv');
        $this->command->info('═══════════════════════════════════════════════════════════════');
    }

    // -----------------------------------------------------------------------
    // Donors
    // -----------------------------------------------------------------------
    private function seedDonors(Role $role): array
    {
        $rows = [];
        for ($i = 1; $i <= self::NUM_DONORS; $i++) {
            $email = sprintf('perf+donor+%02d@nubl.test', $i);
            $phone = '96650' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Perf Donor {$i}",
                    'password' => $this->passwordHash,
                    'email_verified_at' => now(),
                    'phone_number' => $phone,
                    'phone_verified_at' => now(),
                    'membership_type' => User::MEMBERSHIP_DONOR,
                    'status' => User::STATUS_ACTIVE,
                    'is_active' => true,
                ],
            );
            $user->assignRole($role);
            $rows[] = ['role' => 'donor', 'email' => $email, 'password' => self::PASSWORD];
        }
        return $rows;
    }

    // -----------------------------------------------------------------------
    // Recipients (with profile + KYC)
    // -----------------------------------------------------------------------
    private function seedRecipients(Role $role): array
    {
        $rows = [];
        $incomeBands = ['0-1000', '1000-1500', '1500-2500', '2500-4000'];
        $maritalStatuses = ['single', 'married', 'divorced', 'widowed'];

        for ($i = 1; $i <= self::NUM_RECIPIENTS; $i++) {
            $email = sprintf('perf+recipient+%02d@nubl.test', $i);
            $phone = '96651' . str_pad((string) (2000000 + $i), 7, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Perf Recipient {$i}",
                    'password' => $this->passwordHash,
                    'email_verified_at' => now(),
                    'phone_number' => $phone,
                    'phone_verified_at' => now(),
                    'membership_type' => User::MEMBERSHIP_RECIPIENT,
                    'status' => User::STATUS_ACTIVE,
                    'is_active' => true,
                ],
            );
            $user->assignRole($role);

            RecipientProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nationality' => 'Saudi Arabia',
                    'short_address' => 'Riyadh, Perf Test District',
                    'id_type' => 'national_id',
                    'id_photo_path' => 'recipient_id_photos/perf-placeholder',
                ],
            );

            RecipientKycDetails::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'income_band' => $incomeBands[$i % count($incomeBands)],
                    'household_size' => 2 + ($i % 5),
                    'marital_status' => $maritalStatuses[$i % count($maritalStatuses)],
                    'is_student' => $i % 7 === 0,
                ],
            );

            $rows[] = ['role' => 'recipient', 'email' => $email, 'password' => self::PASSWORD];
        }
        return $rows;
    }

    // -----------------------------------------------------------------------
    // Providers (with profile + operating + financial + documents + wallet + menu items)
    // -----------------------------------------------------------------------
    private function seedProviders(Role $role): array
    {
        $rows = [];

        // Build default operating hours: Sun–Thu open 09–18; Fri closed; Sat open 09–18.
        $operatingHours = [];
        foreach (['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'] as $day) {
            $operatingHours[$day] = ['open' => '09:00', 'close' => '18:00', 'closed' => false];
        }
        $operatingHours['friday'] = ['closed' => true];

        for ($i = 1; $i <= self::NUM_PROVIDERS; $i++) {
            $email = sprintf('perf+provider+%02d@nubl.test', $i);
            $phone = '96652' . str_pad((string) (3000000 + $i), 7, '0', STR_PAD_LEFT);
            $bizName = "Perf Provider {$i}";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $bizName,
                    'password' => $this->passwordHash,
                    'email_verified_at' => now(),
                    'phone_number' => $phone,
                    'phone_verified_at' => now(),
                    'membership_type' => User::MEMBERSHIP_PROVIDER,
                    'status' => User::STATUS_ACTIVE,
                    'is_active' => true,
                ],
            );
            $user->assignRole($role);

            // Profile
            $profile = $user->providerProfile;
            if (! $profile) {
                $profile = ProviderProfile::create([
                    'user_id' => $user->id,
                    'full_name_ar' => "مزود {$i}",
                    'full_name_en' => $bizName,
                    'phone_number' => $phone,
                    'email' => $email,
                    'business_name_ar' => "متجر {$i}",
                    'business_name_en' => $bizName,
                    'unified_number' => '7099' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'business_category' => ['restaurant'],
                    'address_ar' => 'الرياض، حي الاختبار',
                    'address_en' => 'Riyadh, Perf Test District',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Riyadh, Perf Test District',
                ]);
                $user->setRelation('providerProfile', $profile);
            }

            // Wallet
            $profile->loadMissing('ewallet');
            if (! $profile->ewallet) {
                $profile->ewallet()->create([
                    'owner_type' => 'PROVIDER',
                    'balance' => 0,
                    'status' => true,
                ]);
            }

            // Operating info — daily_capacity MUST be > 0 for `isOpenForRecipients` to be true.
            if (! $user->providerOperatingInfo) {
                ProviderOperatingInfo::create([
                    'user_id' => $user->id,
                    'operating_hours' => $operatingHours,
                    'daily_capacity' => 200,
                    'service_type' => ['meal_preparation', 'delivery', 'pickup'],
                    'estimated_preparation_order_time' => '15-25 minutes',
                    'adoption_support' => 'yes',
                ]);
            }

            // Financial
            if (! $user->providerFinancialInfo) {
                ProviderFinancialInfo::create([
                    'user_id' => $user->id,
                    'bank_name' => 'Al Rajhi Bank',
                    'iban' => 'SA03' . str_pad((string) $i, 20, '0', STR_PAD_LEFT),
                    'account_holder_name' => $bizName,
                ]);
            }

            // Documents (placeholders — never inspected during perf tests)
            if (! $user->providerDocuments) {
                ProviderDocuments::create([
                    'user_id' => $user->id,
                    'business_license_path' => 'provider_documents/perf-license-placeholder',
                    'id_or_iqama_path' => 'provider_documents/perf-id-placeholder',
                ]);
            }

            // Menu items — 10 per provider, so the recipient browse + request
            // flows have something to select. Skip if already present.
            $existing = ProviderMenuItem::where('provider_id', $user->id)->count();
            if ($existing < self::MENU_ITEMS_PER_PROVIDER) {
                for ($j = $existing + 1; $j <= self::MENU_ITEMS_PER_PROVIDER; $j++) {
                    ProviderMenuItem::create([
                        'provider_id' => $user->id,
                        'name' => "Perf Item {$i}-{$j}",
                        'description' => "Performance test menu item #{$j} from provider {$i}",
                        'price' => 5 + ($j * 2), // 7, 9, 11, ... 25 SAR
                        'category' => 'Other',
                        'max_per_request' => 5,
                        'is_active' => true,
                        'is_admin_blocked' => false,
                    ]);
                }
            }

            $rows[] = ['role' => 'provider', 'email' => $email, 'password' => self::PASSWORD];
        }

        return $rows;
    }

    // -----------------------------------------------------------------------
    // Admin
    // -----------------------------------------------------------------------
    private function seedAdmins(Role $role): array
    {
        $email = 'perf+admin+01@nubl.test';
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Perf Admin',
                'password' => $this->passwordHash,
                'email_verified_at' => now(),
                'phone_number' => '966539999999',
                'phone_verified_at' => now(),
                'membership_type' => User::MEMBERSHIP_DONOR, // admin has no donor/recipient/provider membership; pick neutral
                'status' => User::STATUS_ACTIVE,
                'is_active' => true,
            ],
        );
        $user->assignRole($role);

        return [['role' => 'admin', 'email' => $email, 'password' => self::PASSWORD]];
    }

    // -----------------------------------------------------------------------
    // CSV output
    // -----------------------------------------------------------------------
    private function writeCredentialsCsv(array $rows, string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        $fh = fopen($path, 'w');
        fputcsv($fh, ['role', 'email', 'password']);
        foreach ($rows as $r) {
            fputcsv($fh, [$r['role'], $r['email'], $r['password']]);
        }
        fclose($fh);
    }
}
