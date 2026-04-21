<?php

namespace Tests\Unit\Observers;

use App\Contracts\NotificationServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserObserverCoverageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function rejected_recipient_resubmission_to_pending_notifies_admins(): void
    {
        $notifications = Mockery::mock(NotificationServiceInterface::class);
        $notifications->shouldReceive('sendDocumentsResubmittedForReviewToAdmins')
            ->once()
            ->withArgs(fn (User $user): bool => $user->membership_type === User::MEMBERSHIP_RECIPIENT);
        $this->app->instance(NotificationServiceInterface::class, $notifications);

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_REJECTED,
            'is_active' => true,
        ]);

        $user->update(['status' => User::STATUS_PENDING_APPROVAL]);
    }

    #[Test]
    public function non_eligible_membership_type_does_not_trigger_resubmission_notification(): void
    {
        $notifications = Mockery::mock(NotificationServiceInterface::class);
        $notifications->shouldReceive('sendDocumentsResubmittedForReviewToAdmins')->never();
        $this->app->instance(NotificationServiceInterface::class, $notifications);

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_DONOR,
            'status' => User::STATUS_REJECTED,
            'is_active' => true,
        ]);

        $user->update(['status' => User::STATUS_PENDING_APPROVAL]);
    }

    #[Test]
    public function transition_to_pending_from_a_non_rejected_status_does_not_notify_admins(): void
    {
        $notifications = Mockery::mock(NotificationServiceInterface::class);
        $notifications->shouldReceive('sendDocumentsResubmittedForReviewToAdmins')->never();
        $this->app->instance(NotificationServiceInterface::class, $notifications);

        $user = User::factory()->create([
            'membership_type' => User::MEMBERSHIP_RECIPIENT,
            'status' => User::STATUS_ACTIVE,
            'is_active' => true,
        ]);

        $user->update(['status' => User::STATUS_PENDING_APPROVAL]);
    }
}
