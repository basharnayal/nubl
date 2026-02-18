<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recipient\StoreRecipientRequest;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecipientRequestController extends Controller
{
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
        // Week: startOfWeek(SUNDAY) 00:00 -> endOfWeek(SATURDAY) 23:59:59
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SATURDAY);

        // Sum reserved_amount for this week
        // Statuses that reserve budget: PENDING, PROVIDER_APPROVED, ADMIN_PENDING, ADMIN_APPROVED, REDEEMABLE, FULFILLED
        // Exclude: REJECTED, CANCELLED, ADOPTED (Adopted is free for recipient)
        $reservedStatuses = [
            'PENDING',
            'PROVIDER_APPROVED',
            'ADMIN_PENDING',
            'ADMIN_APPROVED',
            'REDEEMABLE',
            'FULFILLED'
        ];

        $weeklyUsed = RequestModel::where('recipient_id', $user->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->whereIn('status', $reservedStatuses)
            ->where('funding_source', '!=', 'PROVIDER_ADOPTION') // Double check, though status ADOPTED usually covers it
            ->sum('reserved_amount');

        if (($weeklyUsed + $totalAmount) > 400) {
            return back()->withErrors(['allowance' => 'You have exceeded your weekly allowance of 400 SAR.'])
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
