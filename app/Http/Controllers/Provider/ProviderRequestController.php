<?php

namespace App\Http\Controllers\Provider;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Services\AuditService;
use App\Http\Services\SystemWalletService;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderRequestController extends Controller
{
    public function __construct(
        private SystemWalletService $systemWalletService,
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = RequestModel::forProvider(auth()->id())
            ->with(['items.menuItem.menuItemCategory', 'redemption.proof'])
            ->latest()
            ->paginate(15);

        return view('provider.requests.index', compact('requests'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $request = RequestModel::forProvider(auth()->id())
            ->with(['items.menuItem.menuItemCategory', 'redemption.proof'])
            ->findOrFail($id);

        return view('provider.requests.show', compact('request'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $requestModel = RequestModel::forProvider(auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'action' => ['required', 'in:adopt,approve,reject'], // adopt->APPROVED, approve->REDEEMABLE, reject->REJECTED; FULFILLED is separate
            'rejection_reason_code' => ['required_if:action,reject', 'string', 'nullable'],
            'rejection_reason_note' => ['nullable', 'string'],
        ]);

        $action = $validated['action'];

        if ($requestModel->status !== 'REQUESTED') {
            return back()->with('error', 'This request is not in a pending state.');
        }

        $result = DB::transaction(function () use ($requestModel, $action, $validated) {
            if ($action === 'adopt') {
                // Provider adopts = pays from own pocket, CITY_FUND not affected
                $requestModel->update([
                    'status' => 'APPROVED',
                    'funding_source' => 'PROVIDER_ADOPTION',
                ]);
                \App\Http\Services\RedemptionService::generateForRequest($requestModel);
                $this->auditService->log('request', 'approved', [
                    'request_id' => $requestModel->id,
                    'provider_id' => auth()->id(),
                    'funding_source' => 'PROVIDER_ADOPTION',
                ]);
                $this->notificationService->sendRequestStatusChanged($requestModel->load('recipient'), 'APPROVED');
                $this->auditService->log('notification', 'sent', [
                    'type' => 'request_status_changed',
                    'recipient_user_id' => $requestModel->recipient_id,
                    'request_id' => $requestModel->id,
                    'status' => 'APPROVED',
                ]);
            } elseif ($action === 'approve') {
                // Provider accepts using City Fund — status REDEEMABLE (recipient can redeem)
                // Transfer happens only at redemption (QR scan), not at approval
                $amount = (float) $requestModel->reserved_amount;
                if (! $this->systemWalletService->hasSufficientBalance($amount)) {
                    return back()->with('error', __('City fund has insufficient balance for this request.'));
                }
                $requestModel->update([
                    'status' => 'REDEEMABLE',
                    'funding_source' => 'CITY_FUND',
                ]);
                \App\Http\Services\RedemptionService::generateForRequest($requestModel);
                $this->auditService->log('request', 'redeemable', [
                    'request_id' => $requestModel->id,
                    'provider_id' => auth()->id(),
                    'funding_source' => 'CITY_FUND',
                ]);
                $this->notificationService->sendRequestStatusChanged($requestModel->load('recipient'), 'REDEEMABLE');
                $this->auditService->log('notification', 'sent', [
                    'type' => 'request_status_changed',
                    'recipient_user_id' => $requestModel->recipient_id,
                    'request_id' => $requestModel->id,
                    'status' => 'REDEEMABLE',
                ]);
            } elseif ($action === 'reject') {
                $requestModel->update([
                    'status' => 'REJECTED',
                    'rejection_reason_code' => $validated['rejection_reason_code'],
                    'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
                ]);
                $this->auditService->log('request', 'rejected', [
                    'request_id' => $requestModel->id,
                    'provider_id' => auth()->id(),
                    'rejection_reason_code' => $validated['rejection_reason_code'],
                ]);
                $this->notificationService->sendRequestStatusChanged($requestModel->load('recipient'), 'REJECTED');
                $this->auditService->log('notification', 'sent', [
                    'type' => 'request_status_changed',
                    'recipient_user_id' => $requestModel->recipient_id,
                    'request_id' => $requestModel->id,
                    'status' => 'REJECTED',
                ]);
            }

            return back()->with('success', __('Request updated successfully.'));
        });

        return $result;
    }
}
