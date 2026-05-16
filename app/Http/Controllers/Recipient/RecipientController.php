<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\Request as RequestModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipientController extends Controller
{
    public function __construct(
        private \App\Services\Recipient\DashboardService $dashboardService
    ) {}

    public function dashboard(Request $request): View
    {
        $data = $this->dashboardService->getDashboardData($request->user());

        return view('recipient.dashboard', $data);
    }

    public function chartDataApi(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
        $recipientId = $request->user()->id;

        $data = $this->dashboardService->activityChartData($recipientId, $date);

        return response()->json($data);
    }

    public function providersList(): View
    {
        $providers = User::query()
            ->where('membership_type', User::MEMBERSHIP_PROVIDER)
            ->where('status', User::STATUS_ACTIVE)
            ->with(['providerProfile', 'providerOperatingInfo'])
            ->has('providerProfile')
            ->orderBy('name')
            ->get();

        return view('recipient.providers-list', ['providers' => $providers]);
    }

    /**
     * Show menu items for a provider (for modal or standalone).
     */
    public function providerMenu(Request $request, User $provider): View
    {
        if ($provider->membership_type !== User::MEMBERSHIP_PROVIDER || ! $provider->providerProfile) {
            abort(404);
        }

        $menuItems = $provider->providerMenuItems()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $providerName = $provider->providerProfile->full_name_en ?? $provider->providerProfile->full_name_ar ?? $provider->name;

        return view('recipient.provider-menu', [
            'provider' => $provider,
            'providerName' => $providerName,
            'menuItems' => $menuItems,
        ]);
    }
}
