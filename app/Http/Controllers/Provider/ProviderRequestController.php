<?php

namespace App\Http\Controllers\Provider;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\IndexProviderRequestsRequest;
use App\Http\Requests\Provider\UpdateProviderRequestActionRequest;
use App\Models\Request as RequestModel;
use App\Services\AuditService;
use App\Services\SystemWalletService;
use Illuminate\Support\Facades\DB;

class ProviderRequestController extends Controller
{
    public function __construct(
        private SystemWalletService $systemWalletService,
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexProviderRequestsRequest $request)
    {
        $validated = $request->validated();

        $dateRangeInvalid = !empty($validated['from']) && !empty($validated['to'])
            && $validated['to'] < $validated['from'];

        $providerId = auth()->id();
        $perPage = $validated['per_page'] ?? 15;

        $query = RequestModel::forProvider($providerId)
            ->with(['items.menuItem.menuItemCategory', 'redemption.proof']);

        if (!$dateRangeInvalid) {
            if (!empty($validated['from'])) {
                $query->whereDate('created_at', '>=', $validated['from']);
            }
            if (!empty($validated['to'])) {
                $query->whereDate('created_at', '<=', $validated['to']);
            }
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (($validated['needs_proof'] ?? null) === '1') {
            $query->whereHas('redemption', function ($q) {
                $q->where('status', 'REDEEMED')
                    ->whereDoesntHave('proof');
            });
        }

        if (!empty($validated['funding_source'])) {
            $query->where('funding_source', $validated['funding_source']);
        }

        if (!empty($validated['q'])) {
            $idQuery = ltrim(trim($validated['q']), '#');
            if ($idQuery !== '' && ctype_digit($idQuery)) {
                $query->where('id', (int) $idQuery);
            }
        }

        $requests = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        $pendingProofCount = RequestModel::forProvider($providerId)
            ->whereHas('redemption', function ($query) {
                $query->where('status', 'REDEEMED')
                    ->whereDoesntHave('proof');
            })
            ->count();

        $filters = [
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'status' => $validated['status'] ?? null,
            'needs_proof' => $validated['needs_proof'] ?? null,
            'funding_source' => $validated['funding_source'] ?? null,
            'q' => $validated['q'] ?? null,
            'per_page' => $perPage,
        ];

        $hasActiveFilters = filled($filters['from'])
            || filled($filters['to'])
            || filled($filters['status'])
            || filled($filters['needs_proof'])
            || filled($filters['funding_source'])
            || filled($filters['q']);

        $filterStatuses = IndexProviderRequestsRequest::FILTER_STATUSES;

        $statusFilterLabels = [
            'REQUESTED' => __('Requested'),
            'APPROVED' => __('Approved'),
            'ADMIN_PENDING' => __('Admin Pending'),
            'ADMIN_APPROVED' => __('Admin Approved'),
            'REDEEMABLE' => __('Redeemable'),
            'FULFILLED' => __('Fulfilled'),
            'REJECTED' => __('Rejected by provider'),
            'CANCELLED' => __('Cancelled'),
            'ADMIN_REJECTED' => __('Rejected by admin'),
        ];

        $view = view('provider.requests.index', compact(
            'requests',
            'pendingProofCount',
            'filters',
            'hasActiveFilters',
            'filterStatuses',
            'statusFilterLabels'
        ));

        if ($dateRangeInvalid) {
            return $view->withErrors([
                'to' => __('The end date must be on or after the start date.'),
            ]);
        }

        return $view;
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
    public function update(UpdateProviderRequestActionRequest $request, string $id)
    {
        $requestModel = RequestModel::forProvider(auth()->id())->findOrFail($id);

        $validated = $request->validated();

        $action = $validated['action'];

        if (!in_array($requestModel->status, ['REQUESTED', 'ADMIN_APPROVED'], true)) {
            return back()->with('error', 'This request is not in a pending state.');
        }

        $result = DB::transaction(function () use ($requestModel, $action, $validated) {
            if ($action === 'adopt') {
                // Provider adopts = pays from own pocket, CITY_FUND not affected
                $requestModel->update([
                    'status' => 'APPROVED',
                    'funding_source' => 'PROVIDER_ADOPTION',
                ]);
                \App\Services\RedemptionService::generateForRequest($requestModel);
                // FR-22.2: explicit audit — adopting provider is credited as donor for this request
                $this->auditService->log('request', 'provider_credited_as_donor', [
                    'request_id' => $requestModel->id,
                    'provider_user_id' => auth()->id(),
                    'funding_source' => 'PROVIDER_ADOPTION',
                    'attribution' => 'provider_as_donor',
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
                if (!$this->systemWalletService->hasSufficientBalance($amount)) {
                    return back()->with('error', __('City fund has insufficient balance for this request.'));
                }
                $requestModel->update([
                    'status' => 'REDEEMABLE',
                    'funding_source' => 'CITY_FUND',
                ]);
                \App\Services\RedemptionService::generateForRequest($requestModel);
                $this->auditService->log('request', 'approved_city_fund', [
                    'request_id' => $requestModel->id,
                    'provider_user_id' => auth()->id(),
                    'funding_source' => 'CITY_FUND',
                    'reserved_amount' => $amount,
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
                $this->auditService->log('request', 'rejected_by_provider', [
                    'request_id' => $requestModel->id,
                    'provider_user_id' => auth()->id(),
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
