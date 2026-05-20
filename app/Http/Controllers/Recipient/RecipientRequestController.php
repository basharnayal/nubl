<?php

namespace App\Http\Controllers\Recipient;

use App\Contracts\NotificationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Jobs\ProcessRecipientFundRetryJob;
use App\Models\Request as RequestModel;
use App\Services\AllocationService;
use App\Services\AuditService;
use App\Services\Recipient\AllowanceService;
use App\Services\Recipient\RequestService;
use App\Services\Recipient\RequestSubmissionService;
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
        private RequestSubmissionService $submissionService,
        private RequestService $requestService,
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
        if (AllowanceService::wouldExceedAllowance($user->id, $totalAmount)) {
            return back()->with('exceeds_allowance', true)->withInput();
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
    public function index(Request $request)
    {
        $requests = $this->requestService->listRequests($request->user(), $request);

        return view('recipient.requests.index', compact('requests'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $request = $this->requestService->getRequestDetails(auth()->user(), (int) $id);

        return view('recipient.requests.show', compact('request'));
    }

    /**
     * Cancel a pending request (FR-R-06).
     * Recipients can cancel requests that are not yet redeemed or in a locked state.
     */
    public function cancel(Request $request, int $id): RedirectResponse
    {
        $success = $this->requestService->cancelRequest($request->user(), $id);

        if (! $success) {
            return back()->with('error', __('This request cannot be cancelled.'));
        }

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
