<?php

namespace App\Http\Controllers;

use App\Models\PurchaseCategory;
use Illuminate\Http\Request;

class PurchaseCategoryController extends Controller
{
    public function index()
    {
        $categories = PurchaseCategory::main()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->withCount('purchases')
            ->orderBy('sort_order')->orderBy('name')
            ->get();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.purchases.categories', compact('categories', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:main,sub',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|required_if:type,sub|exists:purchase_categories,id',
        ]);

        $parentId = $validated['type'] === 'sub' ? $validated['parent_id'] : null;

        $duplicate = PurchaseCategory::where('parent_id', $parentId)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'A category with this name already exists in that group.'])->withInput();
        }

        $nextSortOrder = 1 + (int) PurchaseCategory::where('parent_id', $parentId)->max('sort_order');

        PurchaseCategory::create([
            'name' => $validated['name'],
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()->route('purchase-categories.index')->with('success', 'Category added successfully.');
    }

    public function update(Request $request, PurchaseCategory $purchaseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $duplicate = PurchaseCategory::where('parent_id', $purchaseCategory->parent_id)
            ->where('id', '!=', $purchaseCategory->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['name' => 'A category with this name already exists in that group.'])->withInput();
        }

        $purchaseCategory->update([
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('purchase-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(PurchaseCategory $purchaseCategory)
    {
        if ($purchaseCategory->children()->exists()) {
            return back()->with('error', 'Cannot delete a main category that still has sub-categories. Delete or reassign them first.');
        }

        if ($purchaseCategory->purchases()->exists()) {
            return back()->with('error', 'Cannot delete a category that already has purchase records. Deactivate it instead.');
        }

        $purchaseCategory->delete();

        return redirect()->route('purchase-categories.index')->with('success', 'Category deleted successfully.');
    }
}
