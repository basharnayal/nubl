<?php

namespace Tests\Unit\Auth;

use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ResubmitApplicationService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResubmitApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function resolve_document_path_returns_null_when_file_missing(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/missing.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertNull($service->resolveDocumentPath($user->fresh(), 'id_photo'));
    }

    #[Test]
    public function resolve_document_path_returns_path_when_file_exists(): void
    {
        Storage::fake('local');
        $path = 'recipient_id_photos/x.png';
        Storage::disk('local')->put($path, 'data');
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => $path,
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertSame($path, $service->resolveDocumentPath($user->fresh(), 'id_photo'));
    }

    #[Test]
    public function resubmit_recipient_returns_false_when_kyc_missing(): void
    {
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'recipient_id_photos/x.png',
        ]);

        $service = app(ResubmitApplicationService::class);

        $this->assertFalse($service->resubmitRecipient($user->fresh(), [
            'name' => 'Test',
            'nationality' => 'Saudi Arabia',
            'short_address' => 'Addr',
            'id_type' => 'national_id',
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => '0',
        ], null, null));
    }

    #[Test]
    public function prepare_recipient_edit_data_contains_expected_keys(): void
    {
        $user = User::factory()->create(['membership_type' => User::MEMBERSHIP_RECIPIENT]);
        RecipientProfile::create([
            'user_id' => $user->id,
            'nationality' => 'Saudi Arabia',
            'short_address' => 'x',
            'id_type' => 'national_id',
            'id_photo_path' => 'p.png',
        ]);
        RecipientKycDetails::create([
            'user_id' => $user->id,
            'income_band' => '1000-1500',
            'household_size' => 2,
            'marital_status' => 'single',
            'is_student' => false,
            'address_confirmation' => null,
        ]);

        $data = app(ResubmitApplicationService::class)->prepareRecipientEditData($user->fresh());

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('profile', $data);
        $this->assertArrayHasKey('kyc', $data);
        $this->assertSame($user->id, $data['user']->id);
    }
}
