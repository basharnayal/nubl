<?php

namespace App\Http\Controllers\Recipient;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Http\Services\AllocationService;
use App\Http\Services\AuditService;
use App\Http\Services\RecipientAllowanceService;
use App\Http\Services\RecipientRequestSubmissionService;
use App\Jobs\ProcessRecipientAllowanceRetryJob;
use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\Request as RequestModel;
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
    ) {}

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

        // --- Weekly allowance (FR-6.1 / FR-6.3): if over limit, queue retry (FR-6.4) ---
        if (RecipientAllowanceService::wouldExceedAllowance($user->id, $totalAmount)) {
            RecipientAllowanceRetryCache::storePayload($user->id, [
                'provider_id' => $providerId,
                'items' => $itemsData,
            ]);

            $delaySeconds = (int) config('recipient.allowance_retry_delay_seconds', 60);
            if (RecipientAllowanceRetryCache::tryScheduleJobLock($user->id, $delaySeconds + 10)) {
                ProcessRecipientAllowanceRetryJob::dispatch($user->id)
                    ->delay(now()->addSeconds($delaySeconds));
            }

            $this->auditService->log('request', 'allowance_retry_queued', [
                'recipient_id' => $user->id,
                'provider_id' => $providerId,
                'amount' => $totalAmount,
            ], $user->id);

            RecipientRequestSubmitCooldown::start($user->id, $delaySeconds);

            return back()
                ->with('info', __('Your request could not be placed now due to your weekly allowance. It has been queued and will be retried automatically within :seconds seconds.', ['seconds' => $delaySeconds]))
                ->withInput();
        }

        RecipientAllowanceRetryCache::clear($user->id);

        if (! $this->allocationService->canCoverRequestAmount($totalAmount)) {
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
    public function index()
    {
        $requests = RequestModel::with(['provider.providerProfile', 'items'])
            ->where('recipient_id', auth()->id())
            ->latest()
            ->paginate(10);

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
        if (in_array($request->status, ['APPROVED', 'REDEEMABLE']) && ! $request->redemption) {
            \App\Http\Services\RedemptionService::generateForRequest($request);
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

        if (! $requestModel->isCancellableByRecipient()) {
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
}
