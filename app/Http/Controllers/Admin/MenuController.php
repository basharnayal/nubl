<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('provider')->with('providerProfile');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('category')) {
            $query->whereHas('providerProfile', function ($q) use ($request) {
                $q->whereJsonContains('business_category', $request->category);
            });
        }

        $providers = $query->latest()->paginate(10);

        // Fetch unique categories from all profiles for the filter dropdown
        $allCategories = \App\Models\ProviderProfile::pluck('business_category')
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        return view('admin.menus.index', compact('providers', 'allCategories'));
    }

    public function show(User $provider, Request $request)
    {
        // Must be a provider
        if (! $provider->hasRole('provider')) {
            abort(404);
        }

        $query = ProviderMenuItem::where('provider_id', $provider->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'blocked') {
                $query->where('is_admin_blocked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_active', true)->where('is_admin_blocked', false);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false)->where('is_admin_blocked', false);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $menuItems = $query->latest()->paginate(15);

        // Fetch relevant categories for the filter
        $profile = $provider->providerProfile;
        $businessCategories = $profile?->business_category ?? ['Other'];
        $businessCategory = is_array($businessCategories) && count($businessCategories) > 0
            ? $businessCategories[0]
            : (is_string($businessCategories) ? $businessCategories : 'Other');

        $categories = \App\Models\MenuItemCategory::where('is_active', true);
        if ($businessCategory !== 'Other') {
            $categories->whereIn('business_category', [$businessCategory, 'Other']);
        }
        $categories = $categories->orderBy('name')->get();

        return view('admin.menus.show', compact('provider', 'menuItems', 'categories'));
    }

    public function toggleBlock(Request $request, ProviderMenuItem $item)
    {
        $item->is_admin_blocked = ! $item->is_admin_blocked;
        $item->save();

        // Audit Logging
        app(\App\Services\AuditService::class)->log('admin_menu', $item->is_admin_blocked ? 'blocked' : 'unblocked', [
            'menu_item_id' => $item->id,
            'provider_id' => $item->provider_id,
            'name' => $item->name,
        ]);

        // Notify Provider
        $provider = $item->provider;
        if ($provider) {
            $provider->notify(new \App\Notifications\AdminToggleMenuItemNotification($item, $item->is_admin_blocked));
        }

        return back()->with('success', $item->is_admin_blocked ? __('Item blocked successfully.') : __('Item unblocked successfully.'));
    }
}
