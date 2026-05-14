<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Services\AuditService;
use Illuminate\Http\Request;

class RequestController extends Controller
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

        $systemWalletService = app(\App\Services\SystemWalletService::class);
        $cityFundBalance = $systemWalletService->getSystemWallet()->balance;

        return view('admin.requests.index', compact('requests', 'cityFundBalance'));
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
            abort_unless(auth()->user()->can('requests.approve'), 403);
            $allocationService = app(\App\Services\AllocationService::class);
            if (! $allocationService->canCoverRequestAmount((float) $requestModel->reserved_amount)) {
                return back()->with('error', 'Insufficient overall city fund allocation to approve this request.');
            }

            $requestModel->update([
                'status' => 'ADMIN_APPROVED',
                'funding_source' => 'CITY_FUND',
            ]);
            $requestModel->load(['recipient', 'provider']);
            $this->auditService->log('admin_request', 'decided', [
                'decision' => 'approve',
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_APPROVED',
                'funding_source' => 'CITY_FUND',
            ], auth()->id());
            $this->notificationService->sendRequestStatusChanged($requestModel, 'ADMIN_APPROVED');
            $this->notificationService->sendRequestStatusChangedToProvider($requestModel, 'ADMIN_APPROVED');
            $this->auditService->log('notification', 'sent', [
                'type' => 'request_status_changed',
                'recipient_user_id' => $requestModel->recipient_id,
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_APPROVED',
            ], auth()->id());
        } elseif ($validated['action'] === 'reject') {
            abort_unless(auth()->user()->can('requests.reject'), 403);
            $requestModel->update([
                'status' => 'ADMIN_REJECTED',
                'rejection_reason_code' => $validated['rejection_reason_code'],
                'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
            ]);
            $requestModel->load(['recipient', 'provider']);
            $this->auditService->log('admin_request', 'decided', [
                'decision' => 'reject',
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_REJECTED',
                'rejection_reason_code' => $validated['rejection_reason_code'],
                'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
            ], auth()->id());
            $this->notificationService->sendRequestStatusChanged($requestModel, 'ADMIN_REJECTED');
            $this->notificationService->sendRequestStatusChangedToProvider($requestModel, 'ADMIN_REJECTED');
            $this->auditService->log('notification', 'sent', [
                'type' => 'request_status_changed',
                'recipient_user_id' => $requestModel->recipient_id,
                'request_id' => $requestModel->id,
                'status' => 'ADMIN_REJECTED',
            ], auth()->id());
        }

        return back()->with('success', 'Request processed successfully.');
    }
}
