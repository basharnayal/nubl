<?php

namespace Tests\Feature\Auth;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_donors_can_register(): void
    {
        $response = $this->post('/register', [
            'membership_type' => 'donor',
            'name' => 'Test Donor',
            'phone_number' => '0501234567',
            'email' => 'donor@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.com',
            'membership_type' => 'donor',
            'status' => 'active',
            'phone_number' => '0501234567',
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_requires_membership_type(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('membership_type');
        $this->assertGuest();
    }
}
