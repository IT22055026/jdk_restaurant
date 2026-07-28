<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Http\Request;

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
            'selling_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

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
            'selling_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $ingredient->update($validated);

        return redirect()->route('ingredients.index')->with('success', 'Included item updated successfully');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()->route('ingredients.index')->with('success', 'Included item deleted successfully');
    }
}
