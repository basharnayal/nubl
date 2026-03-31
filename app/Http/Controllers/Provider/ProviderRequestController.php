<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Services\SystemWalletService;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProviderRequestController extends Controller
{
    /** Status values allowed in the incoming-requests filter (matches provider UI). */
    private const FILTER_STATUSES = [
        'REQUESTED',
        'APPROVED',
        'ADMIN_PENDING',
        'ADMIN_APPROVED',
        'REDEEMABLE',
        'FULFILLED',
        'REJECTED',
        'CANCELLED',
        'ADMIN_REJECTED',
    ];

    public function __construct(
        private SystemWalletService $systemWalletService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $httpRequest)
    {
        $validated = $httpRequest->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(self::FILTER_STATUSES)],
            'needs_proof' => ['nullable', 'in:1'],
            'q' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ]);

        $dateRangeInvalid = ! empty($validated['from']) && ! empty($validated['to'])
            && $validated['to'] < $validated['from'];

        $providerId = auth()->id();
        $perPage = $validated['per_page'] ?? 15;

        $query = RequestModel::forProvider($providerId)
            ->with(['items.menuItem.menuItemCategory', 'redemption.proof']);

        if (! $dateRangeInvalid) {
            if (! empty($validated['from'])) {
                $query->whereDate('created_at', '>=', $validated['from']);
            }
            if (! empty($validated['to'])) {
                $query->whereDate('created_at', '<=', $validated['to']);
            }
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (($validated['needs_proof'] ?? null) === '1') {
            $query->whereHas('redemption', function ($q) {
                $q->where('status', 'REDEEMED')
                    ->whereDoesntHave('proof');
            });
        }

        if (! empty($validated['q'])) {
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
            'q' => $validated['q'] ?? null,
            'per_page' => $perPage,
        ];

        $hasActiveFilters = filled($filters['from'])
            || filled($filters['to'])
            || filled($filters['status'])
            || filled($filters['needs_proof'])
            || filled($filters['q']);

        $filterStatuses = self::FILTER_STATUSES;

        $thisWeekFrom = now()->startOfWeek()->toDateString();
        $thisWeekTo = now()->toDateString();

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
            'thisWeekFrom',
            'thisWeekTo',
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
            } elseif ($action === 'reject') {
                $requestModel->update([
                    'status' => 'REJECTED',
                    'rejection_reason_code' => $validated['rejection_reason_code'],
                    'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
                ]);
            }

            return back()->with('success', __('Request updated successfully.'));
        });

        return $result;
    }
}
