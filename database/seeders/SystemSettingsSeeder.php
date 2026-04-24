<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'qr_ttl_minutes'           => '180',
            'weekly_allowance_limit'    => '400',
            'allocation_paused_global'  => '0',
            'allocation_paused_reason'  => '',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('✓ Seeded ' . count($settings) . ' system settings');
    }
}
