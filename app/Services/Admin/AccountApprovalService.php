<?php

namespace App\Services\Admin;

use App\Contracts\NotificationServiceInterface;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;

class AccountApprovalService
{
    public function __construct(
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function getPendingUsers(): Collection
    {
        return User::whereIn('status', [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])
            ->whereIn('membership_type', [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER])
            ->with(['recipientProfile', 'recipientKycDetails', 'providerProfile', 'providerDocuments'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [User::STATUS_PENDING_APPROVAL])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function approve(User $user, int $adminId): void
    {
        $user->update(['status' => User::STATUS_ACTIVE, 'rejection_reason' => null]);

        $this->auditService->log('account_approval', 'approved', [
            'decision' => 'approve',
            'user_id' => $user->id,
            'email' => $user->email,
            'membership_type' => $user->membership_type,
        ], $adminId);

        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            $this->notificationService->sendAccountStatusUpdated($user, true);
            $this->auditService->log('notification', 'sent', [
                'type' => 'account_approved',
                'recipient_user_id' => $user->id,
            ], $adminId);
        }
    }

    public function reject(User $user, string $reason, int $adminId): void
    {
        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->auditService->log('account_approval', 'rejected', [
            'decision' => 'reject',
            'user_id' => $user->id,
            'email' => $user->email,
            'rejection_reason' => $reason,
        ], $adminId);

        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            $this->notificationService->sendAccountStatusUpdated($user, false, $reason);
            $this->auditService->log('notification', 'sent', [
                'type' => 'account_rejected',
                'recipient_user_id' => $user->id,
            ], $adminId);
        }
    }

    public function resolveFilePath(User $user, string $type): ?string
    {
        if ($user->membership_type === User::MEMBERSHIP_PROVIDER && $user->providerDocuments) {
            return match ($type) {
                'business_license' => $user->providerDocuments->business_license_path,
                'id_or_iqama' => $user->providerDocuments->id_or_iqama_path,
                default => null,
            };
        }

        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT && $type === 'id_photo' && $user->recipientProfile) {
            return $user->recipientProfile->id_photo_path;
        }

        return null;
    }
}
