<?php

namespace Tests\Unit\Permissions;

use App\Support\PermissionDefinitions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionDefinitionsTest extends TestCase
{
    #[Test]
    public function all_permissions_contains_the_exact_union_of_all_groups(): void
    {
        $expected = array_merge(
            PermissionDefinitions::admin(),
            PermissionDefinitions::donor(),
            PermissionDefinitions::recipient(),
            PermissionDefinitions::provider(),
        );

        $this->assertSame($expected, PermissionDefinitions::all());
    }

    #[Test]
    public function all_permissions_are_unique(): void
    {
        $all = PermissionDefinitions::all();

        $this->assertCount(count(array_unique($all)), $all, 'Duplicate permission names exist in PermissionDefinitions::all().');
    }

    #[Test]
    public function ui_groups_match_their_permission_sets(): void
    {
        $groups = collect(PermissionDefinitions::uiGroups())->keyBy('key');

        $this->assertSame(PermissionDefinitions::admin(), $groups->get('admin')['names']);
        $this->assertSame(PermissionDefinitions::donor(), $groups->get('donor')['names']);
        $this->assertSame(PermissionDefinitions::recipient(), $groups->get('recipient')['names']);
        $this->assertSame(PermissionDefinitions::provider(), $groups->get('provider')['names']);
    }
}

