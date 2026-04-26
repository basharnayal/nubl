<?php

namespace App\Http\Controllers\Recipient;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Jobs\ProcessRecipientAllowanceRetryJob;
use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\Request as RequestModel;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\RecipientAllowanceService;
use App\Services\RecipientRequestSubmissionService;
use App\Support\RecipientAllowanceRetryCache;
use App\Support\RecipientFundRetryCache;
use App\Support\RecipientRequestSubmitCooldown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecipientRequestController extends Controller
{
    public function __construct(
        private AllocationService $allocationService,
        private AuditService $auditService,
        private RecipientRequestSubmissionService $submissionService,
        private NotificationServiceInterface $notificationService
    ) {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecipientRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $providerId = $validated['provider_id'];
        $itemsData = $validated['items'];

        if (RecipientRequestSubmitCooldown::active($user->id)) {
            $remaining = RecipientRequestSubmitCooldown::secondsRemaining($user->id);

            return back()
                ->with('error', __('You must wait :seconds seconds before submitting another request while a previous attempt is being retried.', ['seconds' => $remaining]))
                ->withInput();
        }

        $computed = $this->submissionService->computeLineItems($providerId, $itemsData);
        $totalAmount = $computed['total'];

        if ($request->boolean('force_admin_review')) {
            $created = $this->submissionService->createRequest($user, $providerId, $itemsData, 'ADMIN_PENDING');
            RecipientRequestSubmitCooldown::clear($user->id);
            return redirect()
                ->route('recipient.requests.show', $created->id)
                ->with('request_submitted', true)
                ->with('success', __('Request submitted for admin review successfully.'));
        }

        // --- Weekly allowance check ---
        // If over limit and no force_admin_review flag, prompt user to decide
        if (RecipientAllowanceService::wouldExceedAllowance($user->id, $totalAmount)) {
            return back()->with('exceeds_allowance', true)->withInput();
        }

        RecipientAllowanceRetryCache::clear($user->id);

        if (!$this->allocationService->canCoverRequestAmount($totalAmount)) {
            RecipientFundRetryCache::storePayload($user->id, [
                'provider_id' => $providerId,
                'items' => $itemsData,
            ]);

            $delaySeconds = (int) config('recipient.fund_retry_delay_seconds', 60);
            if (RecipientFundRetryCache::tryScheduleJobLock($user->id, $delaySeconds + 10)) {
                ProcessRecipientFundRetryJob::dispatch($user->id)
                    ->delay(now()->addSeconds($delaySeconds));
            }

            $this->auditService->log('request', 'fund_retry_queued', [
                'recipient_id' => $user->id,
                'provider_id' => $providerId,
                'amount' => $totalAmount,
            ], $user->id);

            RecipientRequestSubmitCooldown::start($user->id, $delaySeconds);

            return back()
                ->with('info', __('Your request could not be placed now because the city fund is temporarily insufficient. It has been queued and will be retried automatically within :seconds seconds.', ['seconds' => $delaySeconds]))
                ->withInput();
        }

        RecipientFundRetryCache::clear($user->id);

        $created = $this->submissionService->createRequest($user, $providerId, $itemsData);

        RecipientRequestSubmitCooldown::clear($user->id);

        return redirect()
            ->route('recipient.requests.show', $created->id)
            ->with('request_submitted', true);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RequestModel::with(['provider.providerProfile', 'items.menuItem', 'redemption'])
            ->where('recipient_id', auth()->id());

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

            // UI-friendly status groups for recipient orders page
            if ($status === 'pending') {
                $query->whereIn('status', ['REQUESTED', 'ADMIN_PENDING']);
            } elseif ($status === 'redeemable') {
                // Ready for QR / pickup (approved or redeemable)
                $query->whereIn('status', ['APPROVED', 'ADMIN_APPROVED', 'REDEEMABLE']);
            } elseif ($status === 'fulfilled') {
                $query->where('status', 'FULFILLED');
            } elseif ($status === 'cancelled') {
                $query->where('status', 'CANCELLED');
            } else {
                // Backward-compat for direct status values
                $query->where('status', $status);
            }
        }

        $requests = $query->latest()->paginate(10);

        return view('recipient.requests.index', compact('requests'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $request = RequestModel::with(['provider.providerProfile', 'items.menuItem', 'redemption'])
            ->where('recipient_id', auth()->id())
            ->findOrFail($id);

        // Ensure redemption token exists for APPROVED/REDEEMABLE (e.g. legacy APPROVED orders)
        if (in_array($request->status, ['APPROVED', 'REDEEMABLE']) && !$request->redemption) {
            \App\Services\RedemptionService::generateForRequest($request);
            $request->load('redemption');
        }

        return view('recipient.requests.show', compact('request'));
    }

    /**
     * Cancel a pending request (FR-R-06).
     * Recipients can cancel requests that are not yet redeemed or in a locked state.
     */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $requestModel = RequestModel::with('redemption')
            ->where('recipient_id', auth()->id())
            ->findOrFail($id);

        if (!$requestModel->isCancellableByRecipient()) {
            return back()->with('error', __('This request cannot be cancelled.'));
        }

        $requestModel->update(['status' => 'CANCELLED']);

        $this->auditService->log('request', 'cancelled_by_recipient', [
            'request_id' => $requestModel->id,
            'recipient_id' => auth()->id(),
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

        return back()->with('success', __('Request cancelled successfully.'));
    }

    /**
     * Cancel an over-limit request and apply a 60-second throttle.
     */
    public function cancelThrottle(Request $request): RedirectResponse
    {
        $user = $request->user();
        RecipientRequestSubmitCooldown::start($user->id, 60);
        return back()->with('success', __('Request cancelled. You must wait 60 seconds before trying again.'));
    }
}
