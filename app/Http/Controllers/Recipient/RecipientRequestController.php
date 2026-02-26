<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Http\Services\AuditService;
use App\Http\Services\RecipientAllowanceService;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;

class RecipientRequestController extends Controller
{
    public function __construct(
        private AuditService $auditService
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

        // Calculate Total Amount
        $totalAmount = 0;
        $requestItemsPayload = [];

        // Fetch fresh prices to avoid client tampering (already validated existence)
        $itemIds = array_column($itemsData, 'id');
        $dbItems = ProviderMenuItem::whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($itemsData as $item) {
            $menuItem = $dbItems[$item['id']];
            $qty = (int) $item['quantity'];
            $price = $menuItem->price; // Snapshot
            $lineTotal = $price * $qty;

            $totalAmount += $lineTotal;

            $requestItemsPayload[] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => $qty,
                'price_snapshot' => $price,
                // 'line_total' calculated in DB or accessor
            ];
        }

        // --- Weekly Allowance Logic ---
        // weekly_used = sum(price_snapshot * quantity) for REDEEMABLE/FULFILLED this week
        // If weekly_used + A > 400 → reject
        if (RecipientAllowanceService::wouldExceedAllowance($user->id, $totalAmount)) {
            return back()->withErrors(['allowance' => __('Weekly allowance exceeded.')])
                ->withInput();
        }

        // Create Request Header
        $req = RequestModel::create([
            'recipient_id' => $user->id,
            'provider_id' => $providerId,
            'reserved_amount' => $totalAmount,
            'funding_source' => 'CITY_FUND', // Default
            'status' => 'PENDING',
            'is_flagged' => false,
        ]);

        // Create Request Items
        foreach ($requestItemsPayload as $payload) {
            $req->items()->create($payload);
        }

        $this->auditService->log('request', 'created', [
            'request_id' => $req->id,
            'recipient_id' => $user->id,
            'provider_id' => $providerId,
            'amount' => $totalAmount,
        ]);

        return back()->with('success', 'Request submitted successfully!');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = RequestModel::with(['provider', 'items'])
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
        $request = RequestModel::with(['provider', 'items.menuItem'])
            ->where('recipient_id', auth()->id())
            ->findOrFail($id);

        return view('recipient.requests.show', compact('request'));
    }
}
