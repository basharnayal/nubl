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
        // Providers are users with role 'provider' and status 'active'
        $query = User::role('provider')->active();

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

        // Optimization: Eager load profile
        $providers = $query->with('providerProfile')->paginate(12);

        return view('recipient.providers.index', compact('providers'));
    }

    /**
     * Display the specified provider and their menu.
     */
    public function show(User $provider, Request $request)
    {
        // Ensure user is actually a provider
        if (!$provider->hasRole('provider')) {
            abort(404);
        }

        // Must be active
        if (!$provider->is_active || $provider->status !== User::STATUS_ACTIVE) {
            abort(404);
        }

        $provider->load('providerProfile');

        $query = ProviderMenuItem::where('provider_id', $provider->id)->active();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->latest()->get();

        // Get categories for filter
        $categories = ProviderMenuItem::where('provider_id', $provider->id)
            ->active()
            ->distinct()
            ->pluck('category');

        $weeklyUsed = RecipientAllowanceService::getWeeklyUsed(auth()->id());
        $weeklyLimit = RecipientAllowanceService::WEEKLY_LIMIT;

        return view('recipient.providers.show', compact('provider', 'menuItems', 'categories', 'weeklyUsed', 'weeklyLimit'));
    }
}
