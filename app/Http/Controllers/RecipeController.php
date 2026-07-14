<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function edit(Product $product)
    {
        if ($product->is_finished_good) {
            return redirect()->route('products.index')->withErrors(['product' => 'Finished goods don\'t use a recipe — their stock is tracked directly via Quantity.']);
        }

        $modules = $this->currentUser()->role->modules()->get();
        $ingredients = Ingredient::where('status', 'active')->orderBy('name')->get();
        $product->load('ingredients');

        return view('modules.products-recipe-edit', [
            'product' => $product,
            'modules' => $modules,
            'ingredients' => $ingredients,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        if ($product->is_finished_good) {
            return redirect()->route('products.index')->withErrors(['product' => 'Finished goods don\'t use a recipe — their stock is tracked directly via Quantity.']);
        }

        $validated = $request->validate([
            'ingredient_id' => 'array',
            'ingredient_id.*' => 'required|distinct|exists:ingredients,id',
            'quantity_per_unit' => 'array',
            'quantity_per_unit.*' => 'required|numeric|min:0.0001',
        ]);

        $ingredientIds = $validated['ingredient_id'] ?? [];
        $quantities = $validated['quantity_per_unit'] ?? [];

        $syncData = [];
        foreach ($ingredientIds as $index => $ingredientId) {
            $syncData[$ingredientId] = ['quantity_per_unit' => $quantities[$index]];
        }

        $product->ingredients()->sync($syncData);

        return redirect()->route('products.recipe.edit', $product)->with('success', 'Recipe updated successfully');
    }
}
