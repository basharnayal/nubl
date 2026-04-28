<?php

namespace Tests\Unit\Http\Queries\Admin;

use App\Http\Queries\Admin\UserIndexQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'donor', 'recipient', 'provider'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    #[Test]
    public function search_filter_matches_name_or_email(): void
    {
        $target = User::factory()->create([
            'name' => 'Alpha Search',
            'email' => 'alpha.search@example.com',
        ]);
        $target->assignRole('donor');

        $other = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);
        $other->assignRole('donor');

        $requestByName = Request::create('/admin/manage/users', 'GET', ['search' => 'Alpha']);
        $requestByEmail = Request::create('/admin/manage/users', 'GET', ['search' => 'alpha.search']);

        $query = new UserIndexQuery;

        $this->assertSame([$target->id], $query($requestByName, 15)->pluck('id')->all());
        $this->assertSame([$target->id], $query($requestByEmail, 15)->pluck('id')->all());
    }

    #[Test]
    public function role_and_status_filters_are_applied(): void
    {
        $activeProvider = User::factory()->create(['is_active' => true]);
        $activeProvider->assignRole('provider');

        $inactiveProvider = User::factory()->create(['is_active' => false]);
        $inactiveProvider->assignRole('provider');

        $activeDonor = User::factory()->create(['is_active' => true]);
        $activeDonor->assignRole('donor');

        $request = Request::create('/admin/manage/users', 'GET', [
            'role' => 'provider',
            'status' => 'inactive',
        ]);

        $ids = (new UserIndexQuery)($request, 15)->pluck('id')->all();

        $this->assertSame([$inactiveProvider->id], $ids);
        $this->assertNotContains($activeProvider->id, $ids);
        $this->assertNotContains($activeDonor->id, $ids);
    }

    #[Test]
    public function admins_are_sorted_before_non_admin_users(): void
    {
        $admin = User::factory()->create(['created_at' => now()->subDay()]);
        $admin->assignRole('admin');

        $newerNonAdmin = User::factory()->create(['created_at' => now()]);
        $newerNonAdmin->assignRole('donor');

        $request = Request::create('/admin/manage/users', 'GET');
        $results = (new UserIndexQuery)($request, 15);

        $this->assertSame($admin->id, $results->items()[0]->id);
    }
}
