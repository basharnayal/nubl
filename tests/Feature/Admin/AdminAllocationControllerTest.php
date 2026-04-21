<?php

namespace Tests\Feature\Admin;

use App\Models\PendingAllocation;
use App\Models\Request as RequestModel;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAllocationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function status_page_includes_global_pause_flag_provider_list_and_pending_count(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'recipient', 'guard_name' => 'web']);

        $admin = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $providerA = User::factory()->create([
            'name' => 'Provider A',
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $providerA->assignRole('provider');

        $providerB = User::factory()->create([
            'name' => 'Provider B',
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_PROVIDER,
        ]);
        $providerB->assignRole('provider');

        $recipient = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
        ]);
        $recipient->assignRole('recipient');

        $request = RequestModel::create([
            'recipient_id' => $recipient->id,
            'provider_id' => $providerA->id,
            'reserved_amount' => 15.00,
            'status' => 'REQUESTED',
            'funding_source' => 'CITY_FUND',
        ]);

        PendingAllocation::create([
            'request_id' => $request->id,
            'provider_id' => $providerA->id,
            'amount' => 15.00,
            'paused_by' => 'global',
        ]);

        SystemSetting::setValue('allocation_engine.paused', '1');

        $response = $this->actingAs($admin)->get(route('admin.allocation.status'));

        $response->assertOk();
        $response->assertViewIs('admin.allocation.status');
        $response->assertViewHas('globallyPaused', true);
        $response->assertViewHas('pendingCount', 1);
        $response->assertViewHas('providers', function ($providers) use ($providerA, $providerB): bool {
            $ids = $providers->pluck('id')->all();

            return in_array($providerA->id, $ids, true)
                && in_array($providerB->id, $ids, true);
        });
    }
}
