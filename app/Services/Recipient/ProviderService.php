<?php

namespace App\Services\Recipient;

use App\Models\MenuItemCategory;
use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProviderService
{
    /**
     * List providers for recipients with search.
     */
    public function listProviders(Request $request): LengthAwarePaginator
    {
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

        return $query->with(['providerProfile', 'providerOperatingInfo'])->paginate(12);
    }

    /**
     * Get menu items and categories for a specific provider.
     */
    public function getProviderMenu(User $provider, Request $request): array
    {
        // Ensure user is actually a provider and open
        if (! $provider->hasRole('provider') || ! $provider->isOpenForRecipients()) {
            abort(404);
        }

        $provider->load(['providerProfile', 'providerOperatingInfo']);

        $query = ProviderMenuItem::where('provider_id', $provider->id)->active();

        if ($request->filled('category_id')) {
            $cat = MenuItemCategory::find($request->category_id);
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
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->latest()->get();

        // Get categories for filter
        $businessCategories = $provider->providerProfile?->business_category ?? ['Other'];
        $bCat = is_array($businessCategories) && count($businessCategories) > 0 
            ? $businessCategories[0] 
            : (is_string($businessCategories) ? $businessCategories : 'Other');

        $categoriesQuery = MenuItemCategory::where('is_active', true);
        if ($bCat !== 'Other') {
            $categoriesQuery->whereIn('business_category', [$bCat, 'Other']);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        return [
            'provider' => $provider,
            'menuItems' => $menuItems,
            'categories' => $categories,
            'weeklyUsed' => AllowanceService::getWeeklyUsed(auth()->id()),
            'weeklyLimit' => AllowanceService::weeklyLimit(),
        ];
    }
}
