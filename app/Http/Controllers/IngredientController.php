<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::with('supplierRecord');

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $ingredients = $query->orderBy('name')->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();
        $lowStockCount = Ingredient::whereNotNull('low_stock_threshold')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->count();

        return view('modules.ingredients-list', [
            'ingredients' => $ingredients,
            'modules' => $modules,
            'lowStockCount' => $lowStockCount,
            'searchQuery' => $request->search ?? '',
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('modules.ingredients-create', [
            'modules' => $modules,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('ingredients', 'public');
        }

        Ingredient::create($validated);

        return redirect()->route('ingredients.index')->with('success', 'Included item created successfully');
    }

    public function edit(Ingredient $ingredient)
    {
        $modules = $this->currentUser()->role->modules()->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('modules.ingredients-edit', [
            'ingredient' => $ingredient,
            'modules' => $modules,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,' . $ingredient->id,
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            if ($ingredient->image && ! Str::startsWith($ingredient->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($ingredient->image);
            }

            $validated['image'] = $request->file('image')->store('ingredients', 'public');
        } else {
            unset($validated['image']);
        }

        $ingredient->update($validated);

        return redirect()->route('ingredients.index')->with('success', 'Included item updated successfully');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()->route('ingredients.index')->with('success', 'Included item deleted successfully');
    }
}
