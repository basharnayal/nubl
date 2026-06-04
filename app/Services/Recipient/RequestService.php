<?php

namespace App\Services\Recipient;

use App\Contracts\NotificationServiceInterface;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Services\AuditService;
use App\Services\RedemptionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RequestService
{
    public function __construct(
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * List requests for a recipient with filters.
     */
    public function listRequests(User $user, Request $request): LengthAwarePaginator
    {
        $query = RequestModel::with(['provider.providerProfile', 'items.menuItem', 'redemption'])
            ->where('recipient_id', $user->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('provider', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('providerProfile', function ($ppq) use ($search) {
                                $ppq->where('business_name_en', 'like', "%{$search}%")
                                    ->orWhere('business_name_ar', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->status;

            if ($status === 'pending') {
                $query->whereIn('status', ['REQUESTED', 'ADMIN_PENDING']);
            } elseif ($status === 'redeemable') {
                $query->whereIn('status', ['APPROVED', 'ADMIN_APPROVED', 'REDEEMABLE']);
            } elseif ($status === 'fulfilled') {
                $query->where('status', 'FULFILLED');
            } elseif ($status === 'cancelled') {
                $query->where('status', 'CANCELLED');
            } else {
                $query->where('status', $status);
            }
        }

        return $query->orderByRaw("CASE WHEN status IN ('FULFILLED', 'REJECTED', 'ADMIN_REJECTED', 'CANCELLED') THEN 1 ELSE 0 END ASC")
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Get details for a specific request.
     */
    public function getRequestDetails(User $user, int $id): RequestModel
    {
        $request = RequestModel::with(['provider.providerProfile', 'items.menuItem', 'redemption'])
            ->where('recipient_id', $user->id)
            ->findOrFail($id);

        // Ensure redemption token exists for APPROVED/REDEEMABLE
        if (in_array($request->status, ['APPROVED', 'REDEEMABLE']) && ! $request->redemption) {
            RedemptionService::generateForRequest($request);
            $request->load('redemption');
        }

        return $request;
    }

    /**
     * Cancel a request by recipient.
     */
    public function cancelRequest(User $user, int $id): bool
    {
        $requestModel = RequestModel::with('redemption')
            ->where('recipient_id', $user->id)
            ->findOrFail($id);

        if (! $requestModel->isCancellableByRecipient()) {
            return false;
        }

        $requestModel->update(['status' => 'CANCELLED']);

        $this->auditService->log('request', 'cancelled_by_recipient', [
            'request_id' => $requestModel->id,
            'recipient_id' => $user->id,
        ]);

        $this->notificationService->sendRequestStatusChangedToProvider(
            $requestModel->load('provider'),
            'CANCELLED'
        );

        $this->auditService->log('notification', 'sent', [
            'type' => 'request_status_changed',
            'provider_user_id' => $requestModel->provider_id,
            'request_id' => $requestModel->id,
            'status' => 'CANCELLED',
        ]);

        return true;
    }
}
