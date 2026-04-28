<?php

namespace Database\Seeders;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProviderSeeder extends Seeder
{
    /**
     * Seed provider users with profile, operating info, financial info, and documents.
     */
    public function run(): void
    {
        // Copy provider logos from public/images/seed/provider-logos/ (git-tracked)
        // into storage/app/public/provider-logos/ so teammates get them automatically on db:seed.
        $logosSource = public_path('images/seed/provider-logos');
        $logosDest = storage_path('app/public/provider-logos');

        if (File::isDirectory($logosSource)) {
            File::ensureDirectoryExists($logosDest);
            File::copyDirectory($logosSource, $logosDest);
            $this->command->info('Provider logos copied to storage.');
        } else {
            $this->command->warn('Provider logos folder not found at public/images/seed/provider-logos — skipping.');
        }

        $providerRole = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);

        $providers = [
            [
                'user' => [
                    'name' => 'Al-Rashid Kitchen',
                    'email' => 'provider@nubl.com',
                    'phone_number' => '966502345678',
                ],
                'profile' => [
                    'full_name_ar' => 'مطبخ الراشد',
                    'full_name_en' => 'Al-Rashid Kitchen',
                    'phone_number' => '966502345678',
                    'email' => 'provider@nubl.com',
                    'business_name_ar' => 'مطبخ الراشد',
                    'business_name_en' => 'Al-Rashid Kitchen',
                    'unified_number' => '7000123456',
                    'business_category' => ['restaurant', 'catering'],
                    'address_ar' => 'الرياض، حي الملز',
                    'address_en' => 'Riyadh, Al Malaz District',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Riyadh, Al Malaz',
                ],
                'operating' => [
                    'daily_capacity' => 50,
                    'service_type' => ['meal_preparation', 'delivery'],
                    'estimated_preparation_order_time' => '30 minutes',
                    'adoption_support' => 'yes',
                ],
                'financial' => [
                    'bank_name' => 'Al Rajhi Bank',
                    'iban' => 'SA0380000000608010167519',
                    'account_holder_name' => 'Al-Rashid Kitchen',
                ],
            ],
            [
                'user' => [
                    'name' => 'Community Kitchen',
                    'email' => 'community@nubl.com',
                    'phone_number' => '966503456789',
                ],
                'profile' => [
                    'full_name_ar' => 'مطبخ المجتمع',
                    'full_name_en' => 'Community Kitchen',
                    'phone_number' => '966503456789',
                    'email' => 'community@nubl.com',
                    'business_name_ar' => 'مطبخ المجتمع',
                    'business_name_en' => 'Community Kitchen',
                    'unified_number' => '7000123457',
                    'business_category' => ['catering', 'bakery'],
                    'address_ar' => 'المدينة المنورة، المنطقة الغربية',
                    'address_en' => 'Medina, Western Region',
                    'city' => 'medina',
                    'region' => 'western',
                    'location' => 'Medina',
                ],
                'operating' => [
                    'daily_capacity' => 30,
                    'service_type' => ['meal_preparation', 'pickup'],
                    'estimated_preparation_order_time' => '45 minutes',
                    'adoption_support' => 'partially',
                ],
                'financial' => [
                    'bank_name' => 'SNB',
                    'iban' => 'SA0380000000608010167520',
                    'account_holder_name' => 'Community Kitchen',
                ],
            ],
            // Retail & food chains — mixed business categories (demo data inspired by real KSA brands)
            [
                'user' => [
                    'name' => 'Panda',
                    'email' => 'panda@nubl.com',
                    'phone_number' => '966501111001',
                ],
                'profile' => [
                    'full_name_ar' => 'بنده',
                    'full_name_en' => 'Panda',
                    'phone_number' => '966501111001',
                    'email' => 'panda@nubl.com',
                    'business_name_ar' => 'بنده',
                    'business_name_en' => 'Panda',
                    'unified_number' => '7001000001',
                    'business_category' => ['grocery'],
                    'address_ar' => 'الرياض، طريق الملك فهد',
                    'address_en' => 'Riyadh, King Fahd Road',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Riyadh, King Fahd Road',
                    'logo_path' => 'provider-logos/panda.png',
                ],
                'operating' => [
                    'daily_capacity' => 200,
                    'service_type' => ['both_delivery_pickup'],
                    'estimated_preparation_order_time' => 'Same day (pick slots)',
                    'adoption_support' => 'yes',
                ],
                'financial' => [
                    'bank_name' => 'Al Rajhi Bank',
                    'iban' => 'SA0380000000608010167601',
                    'account_holder_name' => 'Panda',
                ],
            ],
            [
                'user' => [
                    'name' => 'Danube',
                    'email' => 'danube@nubl.com',
                    'phone_number' => '966501111002',
                ],
                'profile' => [
                    'full_name_ar' => 'دانوب',
                    'full_name_en' => 'Danube',
                    'phone_number' => '966501111002',
                    'email' => 'danube@nubl.com',
                    'business_name_ar' => 'دانوب',
                    'business_name_en' => 'Danube',
                    'unified_number' => '7001000002',
                    'business_category' => ['grocery'],
                    'address_ar' => 'جدة، حي الزهراء',
                    'address_en' => 'Jeddah, Al Zahra District',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Jeddah, Al Zahra',
                    'logo_path' => 'provider-logos/Danube.png',
                ],
                'operating' => [
                    'daily_capacity' => 150,
                    'service_type' => ['delivery', 'pickup'],
                    'estimated_preparation_order_time' => '24–48 hours',
                    'adoption_support' => 'partially',
                ],
                'financial' => [
                    'bank_name' => 'SNB',
                    'iban' => 'SA0380000000608010167602',
                    'account_holder_name' => 'Danube',
                ],
            ],
            [
                'user' => [
                    'name' => "McDonald's",
                    'email' => 'mcdonalds@nubl.com',
                    'phone_number' => '966501111003',
                ],
                'profile' => [
                    'full_name_ar' => 'ماكدونالدز',
                    'full_name_en' => "McDonald's",
                    'phone_number' => '966501111003',
                    'email' => 'mcdonalds@nubl.com',
                    'business_name_ar' => 'ماكدونالدز',
                    'business_name_en' => "McDonald's",
                    'unified_number' => '7001000003',
                    'business_category' => ['restaurant'],
                    'address_ar' => 'المدينة المنورة، طريق قربان',
                    'address_en' => 'Medina, Qurban Road',
                    'city' => 'medina',
                    'region' => 'western',
                    'location' => 'Medina',
                    'logo_path' => 'provider-logos/McDonald\'s.png',
                ],
                'operating' => [
                    'daily_capacity' => 120,
                    'service_type' => ['meal_preparation', 'delivery', 'pickup'],
                    'estimated_preparation_order_time' => '15–25 minutes',
                    'adoption_support' => 'yes',
                ],
                'financial' => [
                    'bank_name' => 'Riyad Bank',
                    'iban' => 'SA0380000000608010167603',
                    'account_holder_name' => "McDonald's",
                ],
            ],
            [
                'user' => [
                    'name' => 'Carrefour Saudi',
                    'email' => 'carrefour@nubl.com',
                    'phone_number' => '966501111004',
                ],
                'profile' => [
                    'full_name_ar' => 'كارفور السعودية',
                    'full_name_en' => 'Carrefour Saudi Arabia',
                    'phone_number' => '966501111004',
                    'email' => 'carrefour@nubl.com',
                    'business_name_ar' => 'كارفور',
                    'business_name_en' => 'Carrefour',
                    'unified_number' => '7001000004',
                    'business_category' => ['grocery'],
                    'address_ar' => 'الرياض، حي العليا',
                    'address_en' => 'Riyadh, Olaya District',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Riyadh, Olaya',
                    'logo_path' => 'provider-logos/Carrefour.jpg',
                ],
                'operating' => [
                    'daily_capacity' => 250,
                    'service_type' => ['both_delivery_pickup'],
                    'estimated_preparation_order_time' => 'Same day',
                    'adoption_support' => 'yes',
                ],
                'financial' => [
                    'bank_name' => 'Alinma Bank',
                    'iban' => 'SA0380000000608010167604',
                    'account_holder_name' => 'Carrefour Saudi',
                ],
            ],
            // Shawarma House — real Saudi chain (shawarmahouse.com.sa; CS 9200 16666). Login is demo @nubl.com; IBAN/CR are placeholders.
            [
                'user' => [
                    'name' => 'Shawarma House',
                    'email' => 'shawarmahouse@nubl.com',
                    'phone_number' => '966920016666',
                ],
                'profile' => [
                    'full_name_ar' => 'شاورما هاوس',
                    'full_name_en' => 'Shawarma House',
                    'phone_number' => '966920016666',
                    'email' => 'cs@shawarmahouse.com.sa',
                    'business_name_ar' => 'شاورما هاوس',
                    'business_name_en' => 'Shawarma House',
                    'unified_number' => '7001000006',
                    'business_category' => ['restaurant'],
                    'address_ar' => 'الرياض، حي النسيم الغربي، شارع الأمير ممدوح بن عبدالعزيز',
                    'address_en' => 'Riyadh, An Nasim Al Gharbi, Prince Mamduh Bin Abdulaziz Street (branch area)',
                    'city' => 'Riyadh',
                    'region' => 'western',
                    'location' => 'Riyadh — An Nasim Al Gharbi',
                    'logo_path' => 'provider-logos/shawarma house.png',
                ],
                'operating' => [
                    'daily_capacity' => 200,
                    'service_type' => ['meal_preparation', 'delivery', 'pickup'],
                    'estimated_preparation_order_time' => '15–25 minutes',
                    'adoption_support' => 'partially',
                ],
                'financial' => [
                    'bank_name' => 'SNB',
                    'iban' => 'SA0380000000608010167606',
                    'account_holder_name' => 'Shawarma House',
                ],
            ],
        ];

        foreach ($providers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'name' => $data['user']['name'],
                    'email' => $data['user']['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'membership_type' => User::MEMBERSHIP_PROVIDER,
                    'status' => User::STATUS_ACTIVE,
                    'phone_number' => $data['user']['phone_number'],
                    'phone_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->assignRole($providerRole);

            // Create or fetch provider profile (avoid stale relation cache).
            $profile = $user->providerProfile;
            if (! $profile) {
                $profile = ProviderProfile::create(array_merge($data['profile'], ['user_id' => $user->id]));
                $user->setRelation('providerProfile', $profile);
            }

            // Ensure provider wallet exists (seeders run with WithoutModelEvents, so the profile "created" hook may not fire).
            $profile->loadMissing('ewallet');
            if (! $profile->ewallet) {
                $profile->ewallet()->create([
                    'owner_type' => 'PROVIDER',
                    'balance' => 0,
                    'status' => true,
                ]);
            }

            if (! $user->providerOperatingInfo) {
                $operatingHours = [];
                foreach (array_keys(config('provider.weekdays')) as $day) {
                    $operatingHours[$day] = $day === 'friday' ? ['closed' => true] : [
                        'open' => '09:00',
                        'close' => '18:00',
                        'closed' => false,
                    ];
                }
                ProviderOperatingInfo::create([
                    'user_id' => $user->id,
                    'operating_hours' => $operatingHours,
                    'daily_capacity' => $data['operating']['daily_capacity'],
                    'service_type' => $data['operating']['service_type'],
                    'estimated_preparation_order_time' => $data['operating']['estimated_preparation_order_time'],
                    'adoption_support' => $data['operating']['adoption_support'],
                ]);
            }

            if (! $user->providerFinancialInfo) {
                ProviderFinancialInfo::create(array_merge($data['financial'], ['user_id' => $user->id]));
            }

            if (! $user->providerDocuments) {
                ProviderDocuments::create([
                    'user_id' => $user->id,
                    'business_license_path' => 'provider_documents/seed-license-placeholder',
                    'id_or_iqama_path' => 'provider_documents/seed-id-placeholder',
                ]);
            }
        }

        // Demo logos under storage/app/public/provider-logos/ — sync paths for existing profiles seeded before logo_path existed
        $demoLogos = [
            'panda@nubl.com' => 'provider-logos/panda.png',
            'danube@nubl.com' => 'provider-logos/Danube.png',
            'mcdonalds@nubl.com' => 'provider-logos/McDonald\'s.png',
            'carrefour@nubl.com' => 'provider-logos/Carrefour.jpg',
            'shawarmahouse@nubl.com' => 'provider-logos/shawarma house.png',
        ];
        foreach ($demoLogos as $email => $logoPath) {
            User::query()
                ->where('email', $email)
                ->first()
                ?->providerProfile
                ?->update(['logo_path' => $logoPath]);
        }
    }
}
