<?php

namespace Tests\Unit\Models;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserFindByPhoneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function find_by_phone_matches_user_table(): void
    {
        $user = User::factory()->create([
            'phone_number' => '966501234567',
        ]);

        $this->assertTrue($user->is(User::findByPhone('966501234567')));
        $this->assertNull(User::findByPhone('966509999999'));
    }

    #[Test]
    public function find_by_phone_matches_provider_profile_phone(): void
    {
        $user = User::factory()->create(['phone_number' => null]);
        ProviderProfile::create([
            'user_id' => $user->id,
            'full_name_ar' => 'ت',
            'full_name_en' => 'T',
            'phone_number' => '966507654321',
            'email' => 'p@test.com',
            'business_name_ar' => 'ب',
            'business_name_en' => 'B',
            'unified_number' => '7000000001',
            'business_category' => ['Other'],
            'address_ar' => 'a',
            'address_en' => 'a',
            'city' => 'Riyadh',
            'region' => 'Riyadh',
            'location' => '0,0',
        ]);

        $this->assertTrue($user->is(User::findByPhone('966507654321')));
    }
}
