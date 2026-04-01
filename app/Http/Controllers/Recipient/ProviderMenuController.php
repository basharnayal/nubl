<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Http\Services\RecipientAllowanceService;
use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Http\Request;

class ProviderMenuController extends Controller
{
    /**
     * Display a listing of providers.
     */
    public function index(Request $request)
    {
        // Providers: account active + shop open (accepting_orders), not admin-deactivated
        $query = User::role('provider')->openForRecipients();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('providerProfile', function ($pq) use ($search) {
                        $pq->where('business_name_en', 'like', "%{$search}%")
                            ->orWhere('business_name_ar', 'like', "%{$search}%");
                    });
            });
        }

        // Optimization: Eager load profile and operating info (for service_type display)
        $providers = $query->with(['providerProfile', 'providerOperatingInfo'])->paginate(12);

        return view('recipient.providers.index', compact('providers'));
    }

    /**
     * Display the specified provider and their menu.
     */
    public function show(User $provider, Request $request)
    {
        // Ensure user is actually a provider
        if (! $provider->hasRole('provider')) {
            abort(404);
        }

        if (! $provider->isOpenForRecipients()) {
            abort(404);
        }

        $provider->load(['providerProfile', 'providerOperatingInfo']);

        $query = ProviderMenuItem::where('provider_id', $provider->id)->active();

        if ($request->filled('category_id')) {
            $cat = \App\Models\MenuItemCategory::find($request->category_id);
            if ($cat) {
                $query->where(function ($q) use ($cat) {
                    $q->where('category_id', $cat->id)
                        ->orWhere(function ($sq) use ($cat) {
                            $sq->whereNull('category_id')
                                ->where('category', $cat->name);
                        });
                });
            }
        } elseif ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $menuItems = $query->latest()->get();

        // Get categories for filter
        $businessCategories = $provider->providerProfile?->business_category ?? ['Other'];
        $bCat = is_array($businessCategories) && count($businessCategories) > 0 ? $businessCategories[0] : (is_string($businessCategories) ? $businessCategories : 'Other');

        $categoriesQuery = \App\Models\MenuItemCategory::where('is_active', true);
        if ($bCat !== 'Other') {
            $categoriesQuery->whereIn('business_category', [$bCat, 'Other']);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        $weeklyUsed = RecipientAllowanceService::getWeeklyUsed(auth()->id());
        $weeklyLimit = RecipientAllowanceService::weeklyLimit();

        return view('recipient.providers.show', compact('provider', 'menuItems', 'categories', 'weeklyUsed', 'weeklyLimit'));
    }
}
