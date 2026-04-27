<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreMenuItemRequest;
use App\Http\Requests\Provider\UpdateMenuItemRequest;
use App\Models\ProviderMenuItem;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProviderMenuItem::ownedBy(Auth::id());

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $provider = Auth::user();
        $profile = $provider->providerProfile;
        $businessCategories = $profile?->business_category ?? ['Other'];
        $businessCategory = is_array($businessCategories) && count($businessCategories) > 0
            ? $businessCategories[0]
            : (is_string($businessCategories) ? $businessCategories : 'Other');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        } elseif ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $menuItems = $query->latest()->paginate(10);

        $categoriesQuery = \App\Models\MenuItemCategory::where('is_active', true);
        if ($businessCategory !== 'Other') {
            $categoriesQuery->whereIn('business_category', [$businessCategory, 'Other']);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        return view('provider.menu-items.index', compact('menuItems', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $provider = Auth::user();
        $profile = $provider->providerProfile;
        $businessCategories = $profile?->business_category ?? ['Other'];
        $businessCategory = is_array($businessCategories) && count($businessCategories) > 0
            ? $businessCategories[0]
            : (is_string($businessCategories) ? $businessCategories : 'Other');

        $categoriesQuery = \App\Models\MenuItemCategory::where('is_active', true);
        if ($businessCategory !== 'Other') {
            $categoriesQuery->whereIn('business_category', [$businessCategory, 'Other']);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        return view('provider.menu-items.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest $request)
    {
        $data = $request->validated();
        $data['provider_id'] = Auth::id();

        $category = \App\Models\MenuItemCategory::find($data['category_id']);
        if ($category) {
            $data['category'] = $category->name;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu-items', 'public');
            $data['image_path'] = $path;
        }

        $menuItem = ProviderMenuItem::create($data);

        $this->auditService->log('menu_item', 'created', [
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'category' => $menuItem->category,
        ]);

        return redirect()->route('provider.menu-items.index')
            ->with('success', 'Menu item created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $menuItem = ProviderMenuItem::ownedBy(Auth::id())->findOrFail($id);

        if ($menuItem->is_admin_blocked) {
            return redirect()->route('provider.menu-items.index')
                ->with('error', __('This item is blocked by admin and cannot be edited.'));
        }

        $provider = Auth::user();
        $profile = $provider->providerProfile;
        $businessCategories = $profile?->business_category ?? ['Other'];
        $businessCategory = is_array($businessCategories) && count($businessCategories) > 0
            ? $businessCategories[0]
            : (is_string($businessCategories) ? $businessCategories : 'Other');

        $categoriesQuery = \App\Models\MenuItemCategory::where('is_active', true);
        if ($businessCategory !== 'Other') {
            $categoriesQuery->whereIn('business_category', [$businessCategory, 'Other']);
        }
        $categories = $categoriesQuery->orderBy('name')->get();

        return view('provider.menu-items.edit', compact('menuItem', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, $id)
    {
        $menuItem = ProviderMenuItem::ownedBy(Auth::id())->findOrFail($id);

        if ($menuItem->is_admin_blocked) {
            return redirect()->route('provider.menu-items.index')
                ->with('error', __('This item is blocked by admin and cannot be modified.'));
        }
        $data = $request->validated();

        $category = \App\Models\MenuItemCategory::find($data['category_id']);
        if ($category) {
            $data['category'] = $category->name;
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($menuItem->image_path && Storage::disk('public')->exists($menuItem->image_path)) {
                Storage::disk('public')->delete($menuItem->image_path);
            }

            $path = $request->file('image')->store('menu-items', 'public');
            $data['image_path'] = $path;
        }

        $menuItem->update($data);

        $this->auditService->log('menu_item', 'updated', [
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
        ]);

        return redirect()->route('provider.menu-items.index')
            ->with('success', 'Menu item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $menuItem = ProviderMenuItem::ownedBy(Auth::id())->findOrFail($id);

        if ($menuItem->is_admin_blocked) {
            return redirect()->route('provider.menu-items.index')
                ->with('error', __('This item is blocked by admin and cannot be deactivated.'));
        }

        // Soft delete implementation as requested (deactivate)
        // Or actual delete. The user said: "Prefer soft approach: set is_active = 0"
        // But also said "DELETE /provider/menu-items/{item} (or deactivate)"
        // "Or allow real delete only if required by UI, but default is deactivate"

        // I will implement real delete for now as it makes state management easier for simple CRUD
        // unless I strictly follow "Prefer soft approach".
        // Let's check schema.. constraints might prevent delete if referenced in orders.
        // There are no orders table yet mentioned.
        // Let's stick to simple delete for now to keep it clean, if constraint fails, we can handle exception.
        // Actually, user explicitly said: "set is_active = 0 if column exists (it does)"

        $menuItem->update(['is_active' => false]);

        $this->auditService->log('menu_item', 'deactivated', [
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
        ]);

        return redirect()->route('provider.menu-items.index')
            ->with('success', 'Menu item deactivated successfully.');
    }
}
