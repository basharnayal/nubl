<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = RequestModel::forProvider(auth()->id())
            ->with(['recipient', 'items'])
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
            ->with(['items.menuItem', 'recipient'])
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
            'action' => ['required', 'in:adopt,approve,reject'],
            'rejection_reason_code' => ['required_if:action,reject', 'string', 'nullable'],
            'rejection_reason_note' => ['nullable', 'string'],
        ]);

        $action = $validated['action'];

        if ($requestModel->status !== 'PENDING') {
            return back()->with('error', 'This request is not in a pending state.');
        }

        DB::transaction(function () use ($requestModel, $action, $validated) {
            if ($action === 'adopt') {
                $requestModel->update([
                    'status' => 'ADOPTED',
                    'funding_source' => 'PROVIDER_ADOPTION',
                ]);
            } elseif ($action === 'approve') {
                // Check city fund here if implemented. For now, assume available.
                $requestModel->update([
                    'status' => 'PROVIDER_APPROVED', // Or ADMIN_PENDING if we want admin to review
                    'funding_source' => 'CITY_FUND',
                ]);
            } elseif ($action === 'reject') {
                $requestModel->update([
                    'status' => 'PROVIDER_REJECTED',
                    'rejection_reason_code' => $validated['rejection_reason_code'],
                    'rejection_reason_note' => $validated['rejection_reason_note'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Request updated successfully.');
    }
}
