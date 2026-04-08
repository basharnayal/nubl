<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function index()
    {
        $requests = RequestModel::pendingAdmin()
            ->with(['recipient', 'provider', 'items.menuItem'])
            ->latest()
            ->paginate(15);

        return view('admin.requests.index', compact('requests'));
    }

    public function update(Request $request, string $id)
    {
        $requestModel = RequestModel::findOrFail($id);

        // Ensure only Admin Pending can be acted upon (or maybe Provider Approved needing final check?)
        // For now, assume ADMIN_PENDING is the queue.
        if ($requestModel->status !== 'ADMIN_PENDING') {
            return back()->with('error', 'This request is not pending admin approval.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason_code' => ['required_if:action,reject', 'string', 'nullable'],
            'rejection_reason_note' => ['nullable', 'string'],
        ]);

        if ($validated['action'] === 'approve') {
            $requestModel->update([
                'status' => 'ADMIN_APPROVED', // or REDEEMABLE immediately?
                'funding_source' => 'CITY_FUND', // Confirming source
            ]);
            $requestModel->load(['recipient', 'provider']);
            $this->notificationService->sendRequestStatusChanged($requestModel, 'ADMIN_APPROVED');
            $this->notificationService->sendRequestStatusChangedToProvider($requestModel, 'ADMIN_APPROVED');
            $this->auditService->log('notification', 'sent', [
                'type' => 'request_status_changed',
                'recipient_user_id' => $requestModel->recipient_id,
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_APPROVED',
            ]);
        } elseif ($validated['action'] === 'reject') {
            $requestModel->update([
                'status' => 'ADMIN_REJECTED',
                'rejection_reason_code' => $validated['rejection_reason_code'],
                'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
            ]);
            $requestModel->load(['recipient', 'provider']);
            $this->notificationService->sendRequestStatusChanged($requestModel, 'ADMIN_REJECTED');
            $this->notificationService->sendRequestStatusChangedToProvider($requestModel, 'ADMIN_REJECTED');
            $this->auditService->log('notification', 'sent', [
                'type' => 'request_status_changed',
                'recipient_user_id' => $requestModel->recipient_id,
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_REJECTED',
            ]);
        }

        return back()->with('success', 'Request processed successfully.');
    }
}
