<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure model state (no database).
 */
class UserModelStateTest extends TestCase
{
    #[Test]
    public function can_login_requires_active_and_non_blocked_status(): void
    {
        $active = User::factory()->make([
            'is_active' => true,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->assertTrue($active->canLogin());

        $deactivated = User::factory()->make([
            'is_active' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->assertFalse($deactivated->canLogin());

        $pending = User::factory()->make([
            'is_active' => true,
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
        $this->assertFalse($pending->canLogin());
    }

    #[Test]
    public function has_full_access_only_when_status_active(): void
    {
        $this->assertTrue(User::factory()->make(['status' => User::STATUS_ACTIVE])->hasFullAccess());
        $this->assertFalse(User::factory()->make(['status' => User::STATUS_PENDING_APPROVAL])->hasFullAccess());
    }

    #[Test]
    public function is_open_for_recipients_requires_flags_and_active_status(): void
    {
        $open = User::factory()->make([
            'is_active' => true,
            'accepting_orders' => true,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->assertTrue($open->isOpenForRecipients());

        $closed = User::factory()->make([
            'is_active' => true,
            'accepting_orders' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->assertFalse($closed->isOpenForRecipients());
    }
}
