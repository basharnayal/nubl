<?php

namespace Database\Seeders;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProviderSeeder extends Seeder
{
    /**
     * Seed provider users with profile, operating info, financial info, and documents.
     */
    public function run(): void
    {
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

            if (!$user->providerProfile) {
                ProviderProfile::create(array_merge($data['profile'], ['user_id' => $user->id]));
            }

            if (!$user->providerOperatingInfo) {
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

            if (!$user->providerFinancialInfo) {
                ProviderFinancialInfo::create(array_merge($data['financial'], ['user_id' => $user->id]));
            }

            if (!$user->providerDocuments) {
                ProviderDocuments::create([
                    'user_id' => $user->id,
                    'business_license_path' => 'provider_documents/seed-license-placeholder',
                    'id_or_iqama_path' => 'provider_documents/seed-id-placeholder',
                ]);
            }
        }
    }
}
