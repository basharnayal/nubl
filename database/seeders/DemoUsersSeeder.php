<?php

namespace Database\Seeders;

use App\Models\Ewallet;
use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUsersSeeder extends Seeder
{
    private static string $password;

    public function run(): void
    {
        self::$password = Hash::make('password');

        $this->seedAdmins();
        $this->seedDonors();
        $this->seedRecipients();
        $this->seedProviders();
    }

    // ─── Admins ──────────────────────────────────────────────────

    private function seedAdmins(): void
    {
        if (User::where('email', 'admin2@nubl.com')->exists()) {
            $this->command->info('⏭ Demo admins already seeded.');
            return;
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admins = [
            ['name' => 'Noura Al-Shamrani',  'email' => 'admin2@nubl.com', 'phone_number' => '966509999001', 'weeks_ago' => 8],
        ];

        foreach ($admins as $a) {
            $user = User::create([
                'name'              => $a['name'],
                'email'             => $a['email'],
                'password'          => self::$password,
                'email_verified_at' => now(),
                'membership_type'   => User::MEMBERSHIP_ADMIN,
                'status'            => User::STATUS_ACTIVE,
                'phone_number'      => $a['phone_number'],
                'phone_verified_at' => now(),
                'is_active'         => true,
                'accepting_orders'  => true,
                'created_at'        => now()->subWeeks($a['weeks_ago']),
                'updated_at'        => now()->subWeeks($a['weeks_ago']),
            ]);
            $user->assignRole($adminRole);
        }

        $this->command->info('✓ Seeded ' . count($admins) . ' additional admin(s)');
    }

    // ─── Donors ──────────────────────────────────────────────────

    private function seedDonors(): void
    {
        if (User::where('email', 'demo-donor-01@nubl.com')->exists()) {
            $this->command->info('⏭ Demo donors already seeded.');
            return;
        }

        $donorRole = Role::firstOrCreate(['name' => 'donor', 'guard_name' => 'web']);

        $donors = [
            ['name' => 'Abdullah Al-Qahtani', 'weeks_ago' => 7],
            ['name' => 'Fatimah Al-Dosari',   'weeks_ago' => 7],
            ['name' => 'Mohammed Al-Harbi',   'weeks_ago' => 6],
            ['name' => 'Sara Al-Ghamdi',      'weeks_ago' => 6],
            ['name' => 'Khalid Al-Zahrani',   'weeks_ago' => 5],
            ['name' => 'Amal Al-Shehri',      'weeks_ago' => 5],
            ['name' => 'Omar Al-Mutairi',     'weeks_ago' => 4],
            ['name' => 'Haya Al-Tamimi',      'weeks_ago' => 4],
            ['name' => 'Ahmad Al-Subaie',     'weeks_ago' => 3],
            ['name' => 'Layla Al-Anazi',      'weeks_ago' => 3],
            ['name' => 'Sultan Al-Dossary',   'weeks_ago' => 2],
            ['name' => 'Maha Al-Khaldi',      'weeks_ago' => 2],
            ['name' => 'Turki Al-Juhani',     'weeks_ago' => 1],
            ['name' => 'Nouf Al-Otaibi',      'weeks_ago' => 1],
            ['name' => 'Ibrahim Al-Shahrani', 'weeks_ago' => 0],
        ];

        foreach ($donors as $i => $d) {
            $idx  = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $user = User::create([
                'name'              => $d['name'],
                'email'             => "demo-donor-{$idx}@nubl.com",
                'password'          => self::$password,
                'email_verified_at' => now()->subWeeks($d['weeks_ago']),
                'membership_type'   => User::MEMBERSHIP_DONOR,
                'status'            => User::STATUS_ACTIVE,
                'phone_number'      => '96650500' . str_pad(1001 + $i, 4, '0', STR_PAD_LEFT),
                'phone_verified_at' => now()->subWeeks($d['weeks_ago']),
                'is_active'         => true,
                'accepting_orders'  => true,
                'created_at'        => now()->subWeeks($d['weeks_ago'])->subDays(rand(0, 3)),
                'updated_at'        => now()->subWeeks($d['weeks_ago']),
            ]);
            $user->assignRole($donorRole);
        }

        $this->command->info('✓ Seeded ' . count($donors) . ' demo donors');
    }

    // ─── Recipients ──────────────────────────────────────────────

    private function seedRecipients(): void
    {
        if (User::where('email', 'demo-recipient-01@nubl.com')->exists()) {
            $this->command->info('⏭ Demo recipients already seeded.');
            return;
        }

        $recipientRole = Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $recipients = [
            // 15 active recipients
            ['name' => 'Nasser Al-Shamri',     'weeks_ago' => 7, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Maryam Al-Enezi',      'weeks_ago' => 7, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Saeed Al-Harthy',      'weeks_ago' => 6, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Hind Al-Rashidi',      'weeks_ago' => 6, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Ali Al-Bishi',         'weeks_ago' => 5, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Fatimah Al-Dawsari',   'weeks_ago' => 5, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Youssef Al-Malki',     'weeks_ago' => 4, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Abeer Al-Qahtani',     'weeks_ago' => 4, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Hassan Al-Mutairi',    'weeks_ago' => 3, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Reem Al-Hajri',        'weeks_ago' => 3, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Mansour Al-Shehri',    'weeks_ago' => 2, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Salwa Al-Ghamdi',      'weeks_ago' => 2, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Khaled Al-Otaibi',     'weeks_ago' => 1, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Nawal Al-Tamimi',      'weeks_ago' => 1, 'status' => User::STATUS_ACTIVE],
            ['name' => 'Hamad Al-Dosari',      'weeks_ago' => 0, 'status' => User::STATUS_ACTIVE],
            // 3 pending approval
            ['name' => 'Latifah Al-Harbi',     'weeks_ago' => 0, 'status' => User::STATUS_PENDING_APPROVAL],
            ['name' => 'Faris Al-Zahrani',     'weeks_ago' => 0, 'status' => User::STATUS_PENDING_APPROVAL],
            ['name' => 'Dalal Al-Anazi',       'weeks_ago' => 0, 'status' => User::STATUS_PENDING_APPROVAL],
            // 2 rejected
            ['name' => 'Bader Al-Juhani',      'weeks_ago' => 1, 'status' => User::STATUS_REJECTED],
            ['name' => 'Wafa Al-Khaldi',       'weeks_ago' => 2, 'status' => User::STATUS_REJECTED],
        ];

        $addresses = [
            'Riyadh, Al Malaz District', 'Jeddah, Al Hamra District', 'Medina, Al Haram District',
            'Dammam, Al Faisaliyah', 'Makkah, Al Aziziyah', 'Riyadh, Al Naseem',
            'Jeddah, Al Safa', 'Medina, Quba', 'Dammam, Al Shatea', 'Riyadh, Al Rawdah',
            'Jeddah, Al Khalidiyah', 'Makkah, Al Shoqiyah', 'Riyadh, Al Sulimaniyah',
            'Dammam, Al Muhammadiyah', 'Jeddah, Al Marwah', 'Riyadh, Al Rabwa',
            'Medina, Al Uyun', 'Makkah, Al Rusayfah', 'Jeddah, Al Zahra', 'Riyadh, Al Olaya',
        ];
        $incomeBands = ['0-500', '500-1000', '1000-1500', '1500-2000', '2000-2500', '2500-3000'];
        $nationalities = ['Saudi Arabia', 'Yemen', 'Syria', 'Egypt', 'Jordan', 'Sudan', 'Pakistan'];
        $maritalStatuses = ['single', 'married', 'divorced', 'widowed'];

        foreach ($recipients as $i => $r) {
            $idx   = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $email = "demo-recipient-{$idx}@nubl.com";

            // Use named known accounts for pending/rejected
            if ($r['status'] === User::STATUS_PENDING_APPROVAL && $r['name'] === 'Dalal Al-Anazi') {
                $email = 'pending-recipient@nubl.com';
            }
            if ($r['status'] === User::STATUS_REJECTED && $r['name'] === 'Bader Al-Juhani') {
                $email = 'rejected-recipient@nubl.com';
            }

            $createdAt = now()->subWeeks($r['weeks_ago'])->subDays(rand(0, 4));

            $user = User::create([
                'name'              => $r['name'],
                'email'             => $email,
                'password'          => self::$password,
                'email_verified_at' => $r['status'] === User::STATUS_ACTIVE ? $createdAt : null,
                'membership_type'   => User::MEMBERSHIP_RECIPIENT,
                'status'            => $r['status'],
                'phone_number'      => '96650600' . str_pad(1001 + $i, 4, '0', STR_PAD_LEFT),
                'phone_verified_at' => $r['status'] === User::STATUS_ACTIVE ? $createdAt : null,
                'is_active'         => $r['status'] === User::STATUS_ACTIVE,
                'accepting_orders'  => true,
                'rejection_reason'  => $r['status'] === User::STATUS_REJECTED ? 'Incomplete documentation' : null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ]);
            $user->assignRole($recipientRole);

            // Only create profile/KYC for active & pending recipients
            if (in_array($r['status'], [User::STATUS_ACTIVE, User::STATUS_PENDING_APPROVAL])) {
                RecipientProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nationality'    => $nationalities[$i % count($nationalities)],
                        'short_address'  => $addresses[$i % count($addresses)],
                        'id_type'        => $i % 3 === 0 ? 'iqama' : 'national_id',
                        'id_photo_path'  => 'recipient_id_photos/seed-placeholder',
                    ]
                );

                RecipientKycDetails::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'income_band'          => $incomeBands[$i % count($incomeBands)],
                        'household_size'       => rand(1, 8),
                        'marital_status'       => $maritalStatuses[$i % count($maritalStatuses)],
                        'is_student'           => $i % 5 === 0,
                        'address_confirmation' => null,
                    ]
                );
            }
        }

        $this->command->info('✓ Seeded ' . count($recipients) . ' demo recipients (15 active, 3 pending, 2 rejected)');
    }

    // ─── Providers (additional pending/rejected) ──────────────────

    private function seedProviders(): void
    {
        if (User::where('email', 'pending-provider@nubl.com')->exists()) {
            $this->command->info('⏭ Demo providers already seeded.');
            return;
        }

        $providerRole = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $providers = [
            [
                'name'            => 'Al-Salam Kitchen',
                'email'           => 'pending-provider@nubl.com',
                'phone_number'    => '966507999001',
                'status'          => User::STATUS_PENDING_APPROVAL,
                'business_name'   => 'Al-Salam Kitchen',
                'business_name_ar'=> 'مطبخ السلام',
                'business_category'=> 'Restaurant',
                'city'            => 'Jeddah',
            ],
            [
                'name'            => 'Fresh Bites',
                'email'           => 'rejected-provider@nubl.com',
                'phone_number'    => '966507999002',
                'status'          => User::STATUS_REJECTED,
                'business_name'   => 'Fresh Bites',
                'business_name_ar'=> 'لقيمات طازجة',
                'business_category'=> 'Bakery',
                'city'            => 'Riyadh',
            ],
        ];

        foreach ($providers as $p) {
            $createdAt = now()->subWeeks(rand(1, 3))->subDays(rand(0, 6));

            $user = User::create([
                'name'              => $p['name'],
                'email'             => $p['email'],
                'password'          => self::$password,
                'email_verified_at' => null,
                'membership_type'   => User::MEMBERSHIP_PROVIDER,
                'status'            => $p['status'],
                'phone_number'      => $p['phone_number'],
                'phone_verified_at' => null,
                'is_active'         => false,
                'accepting_orders'  => false,
                'rejection_reason'  => $p['status'] === User::STATUS_REJECTED ? 'Incomplete business license documentation' : null,
                'created_at'        => $createdAt,
                'updated_at'        => $createdAt,
            ]);
            $user->assignRole($providerRole);

            $profile = ProviderProfile::create([
                'user_id'            => $user->id,
                'full_name_ar'       => $p['name'],
                'full_name_en'       => $p['name'],
                'phone_number'       => $p['phone_number'],
                'email'              => $p['email'],
                'business_name_ar'   => $p['business_name_ar'],
                'business_name_en'   => $p['business_name'],
                'unified_number'     => '700' . rand(1000000, 9999999),
                'business_category'  => [$p['business_category']],
                'address_ar'         => "شارع الملك فهد، {$p['city']}",
                'address_en'         => "King Fahd Road, {$p['city']}",
                'city'               => $p['city'],
                'region'             => $p['city'] === 'Riyadh' ? 'Riyadh Region' : 'Makkah Region',
                'logo_path'          => null,
            ]);

            // Manually create ewallet (WithoutModelEvents skips ProviderProfile::booted)
            Ewallet::firstOrCreate(
                ['owner_type' => 'PROVIDER', 'owner_id' => $profile->id],
                ['balance' => 0, 'status' => true]
            );

            ProviderOperatingInfo::create([
                'user_id'                           => $user->id,
                'operating_hours'                   => ['sun' => '08:00-22:00', 'mon' => '08:00-22:00', 'tue' => '08:00-22:00', 'wed' => '08:00-22:00', 'thu' => '08:00-22:00'],
                'service_type'                      => ['meal_preparation', 'pickup'],
                'daily_capacity'                    => rand(30, 100),
                'estimated_preparation_order_time'  => '30 minutes',
                'adoption_support'                  => 'yes',
            ]);

            ProviderFinancialInfo::create([
                'user_id'             => $user->id,
                'bank_name'           => 'Al Rajhi Bank',
                'account_holder_name' => $p['business_name'],
                'iban'                => 'SA' . rand(10, 99) . str_pad(rand(0, 9999999999), 22, '0', STR_PAD_LEFT),
            ]);

            ProviderDocuments::create([
                'user_id'              => $user->id,
                'business_license_path'=> 'provider_documents/demo_license.pdf',
                'id_or_iqama_path'     => 'provider_documents/demo_id.pdf',
            ]);
        }

        $this->command->info('✓ Seeded ' . count($providers) . ' additional providers (1 pending, 1 rejected)');
    }
}
