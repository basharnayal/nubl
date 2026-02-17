<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreMenuItemRequest;
use App\Http\Requests\Provider\UpdateMenuItemRequest;
use App\Http\Services\AuditService;
use App\Models\ProviderMenuItem;
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
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $menuItems = $query->latest()->paginate(10);
        $categories = ProviderMenuItem::ownedBy(Auth::id())->distinct()->pluck('category');

        return view('provider.menu-items.index', compact('menuItems', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // If categories are predefined in code, pass them here. 
        // For now, allow free text or select from existing used categories.
        $categories = ProviderMenuItem::ownedBy(Auth::id())->distinct()->pluck('category');
        return view('provider.menu-items.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest $request)
    {
        $data = $request->validated();
        $data['provider_id'] = Auth::id();

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
        $categories = ProviderMenuItem::ownedBy(Auth::id())->distinct()->pluck('category');
        return view('provider.menu-items.edit', compact('menuItem', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, $id)
    {
        $menuItem = ProviderMenuItem::ownedBy(Auth::id())->findOrFail($id);
        $data = $request->validated();

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
