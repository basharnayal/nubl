<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // إنشاء الأدوار أولاً
        $this->call(RoleSeeder::class);

        // إنشاء مستخدمين تجريبيين
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@nubl.com',
        ]);
        $admin->assignRole('admin');

        $donor = User::factory()->create([
            'name' => 'Donor User',
            'email' => 'donor@nubl.com',
        ]);
        $donor->assignRole('donor');
        
    }
}
