<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProviderMenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Calculate weekly allowance usage
        $weekStart = now()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY);
        $weekEnd = now()->endOfWeek(\Illuminate\Support\Carbon::SATURDAY);

        $weeklyUsed = \App\Models\Request::where('recipient_id', auth()->id())
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->whereIn('status', [
                'PENDING',
                'PROVIDER_APPROVED',
                'ADMIN_PENDING',
                'ADMIN_APPROVED',
                'REDEEMABLE',
                'FULFILLED'
            ])
            ->where('funding_source', '!=', 'PROVIDER_ADOPTION') // Double safety
            ->sum('reserved_amount');

        return view('recipient.providers.show', compact('provider', 'menuItems', 'categories', 'weeklyUsed'));
    }
}
