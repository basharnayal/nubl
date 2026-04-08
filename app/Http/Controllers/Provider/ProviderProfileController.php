<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProviderProfileController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Show the form for editing operating profile (hours, capacity, pickup notes).
     */
    public function edit(): View
    {
        $user = auth()->user();
        $profile = $user->providerProfile;
        $operatingInfo = $user->providerOperatingInfo;

        abort_unless($profile && $operatingInfo, 404);

        $adoptionSupportOptions = collect(config('provider.adoption_support_options', []))
            ->keys()
            ->mapWithKeys(fn (string $key) => [$key => __('provider.adoption.'.$key)])
            ->all();

        return view('provider.profile.edit', [
            'user' => $user,
            'profile' => $profile,
            'operatingInfo' => $operatingInfo,
            'serviceTypes' => config('provider.service_types'),
            'weekdayKeys' => array_keys(config('provider.weekdays')),
            'adoptionSupportOptions' => $adoptionSupportOptions,
        ]);
    }

    /**
     * Update operating hours, capacity, service options, and pickup notes.
     */
    public function update(UpdateProviderProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $operatingInfo = $user->providerOperatingInfo;
        abort_unless($operatingInfo, 404);

        $operatingHours = $request->buildOperatingHours();

        DB::transaction(function () use ($request, $operatingInfo, $operatingHours) {
            $validated = $request->validated();
            $operatingInfo->update([
                'operating_hours' => $operatingHours,
                'daily_capacity' => $validated['daily_capacity'],
                'service_type' => $validated['service_type'],
                'estimated_preparation_order_time' => $validated['estimated_preparation_order_time'],
                'adoption_support' => $validated['adoption_support'],
                'pickup_notes' => $validated['pickup_notes'] ?? null,
            ]);
        });

        $validatedKeys = array_keys($request->validated());
        $this->auditService->log('provider_operating_info', 'updated', [
            'user_id' => $user->id,
            'provider_operating_info_id' => $operatingInfo->id,
            'updated_fields' => $validatedKeys,
        ]);

        return redirect()->route('provider.profile.edit')->with('success', __('Profile updated.'));
    }

    /**
     * Toggle whether the provider shop is open to recipients (accepting orders).
     * Does not change admin-controlled account activation (is_active).
     */
    public function toggleActive(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $acceptingOrdersBefore = (bool) $user->accepting_orders;
        $user->accepting_orders = ! $user->accepting_orders;
        $user->save();

        $open = (bool) $user->accepting_orders;

        $this->auditService->log('provider', 'store_availability_toggled', [
            'user_id' => $user->id,
            'accepting_orders_before' => $acceptingOrdersBefore,
            'accepting_orders_after' => $open,
        ]);

        return back()->with('success', $open
            ? __('Your store is now open. Recipients can see your menu.')
            : __('Your store is paused. Recipients cannot see your menu until you reopen.'));
    }
}
