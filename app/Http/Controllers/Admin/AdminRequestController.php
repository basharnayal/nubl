<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\AuditService;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function __construct(
        private AuditService $auditService
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
            $this->auditService->log('request', 'admin_approved', [
                'request_id' => $requestModel->id,
                'decision' => 'approve',
                'recipient_id' => $requestModel->recipient_id,
                'provider_id' => $requestModel->provider_id,
            ]);
            // Here we might trigger notification to Recipient "Your request is approved!"
        } elseif ($validated['action'] === 'reject') {
            $requestModel->update([
                'status' => 'ADMIN_REJECTED',
                'rejection_reason_code' => $validated['rejection_reason_code'],
                'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
            ]);
            $this->auditService->log('request', 'admin_rejected', [
                'request_id' => $requestModel->id,
                'decision' => 'reject',
                'recipient_id' => $requestModel->recipient_id,
                'provider_id' => $requestModel->provider_id,
                'rejection_reason_code' => $validated['rejection_reason_code'],
                'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
            ]);
        }

        return back()->with('success', 'Request processed successfully.');
    }
}
