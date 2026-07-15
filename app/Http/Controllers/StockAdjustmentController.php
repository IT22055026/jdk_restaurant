<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientStockMovement;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        $modules = $this->currentUser()->role->modules()->get();

        $type = request('type');

        $allMovements = collect();

        if ($type !== 'ingredient') {
            $allMovements = $allMovements->concat(
                StockMovement::with(['product', 'user'])->latest()->take(100)->get()
                    ->map(fn ($m) => [
                        'type' => 'Finished Good',
                        'name' => $m->product?->name ?? 'N/A',
                        'unit' => '',
                        'change_type' => $m->change_type,
                        'quantity' => (float) $m->quantity,
                        'reason' => $m->reason,
                        'source' => $m->source,
                        'user' => $m->user?->name ?? 'System',
                        'created_at' => $m->created_at,
                    ])
            );
        }

        if ($type !== 'finished_good') {
            $allMovements = $allMovements->concat(
                IngredientStockMovement::with(['ingredient', 'user'])->latest()->take(100)->get()
                    ->map(fn ($m) => [
                        'type' => 'Included Item',
                        'name' => $m->ingredient?->name ?? 'N/A',
                        'unit' => $m->ingredient?->unit ?? '',
                        'change_type' => $m->change_type,
                        'quantity' => (float) $m->quantity,
                        'reason' => $m->reason,
                        'source' => $m->source,
                        'user' => $m->user?->name ?? 'System',
                        'created_at' => $m->created_at,
                    ])
            );
        }

        $allMovements = $allMovements->sortByDesc('created_at')->values();

        $page = (int) request('page', 1);
        $perPage = 15;
        $movements = new LengthAwarePaginator(
            $allMovements->forPage($page, $perPage),
            $allMovements->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('modules.stock-adjustments-index', [
            'modules' => $modules,
            'movements' => $movements,
            'type' => $type,
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();
        // Only finished goods carry their own stock; recipe-tracked items are adjusted via ingredients.
        $products = Product::where('is_finished_good', true)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('modules.stock-adjustments-create', [
            'modules' => $modules,
            'products' => $products,
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:product,ingredient',
            'product_id' => 'required_if:item_type,product|nullable|exists:products,id',
            'ingredient_id' => 'required_if:item_type,ingredient|nullable|exists:ingredients,id',
            'change_type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        return $validated['item_type'] === 'product'
            ? $this->adjustProduct($validated)
            : $this->adjustIngredient($validated);
    }

    private function adjustProduct(array $validated)
    {
        $product = Product::find($validated['product_id']);

        if (!$product->is_finished_good) {
            return back()->withErrors(['product_id' => 'Only finished goods carry their own stock — recipe-tracked items are adjusted via their included items instead.']);
        }

        if (floor($validated['quantity']) != $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Quantity must be a whole number for finished goods.']);
        }

        $quantity = (int) $validated['quantity'];

        if ($validated['change_type'] === 'decrease' && !$product->is_unlimited_stock && $product->quantity < $quantity) {
            return back()->withErrors(['quantity' => 'Not enough quantity available']);
        }

        if (!$product->is_unlimited_stock) {
            if ($validated['change_type'] === 'increase') {
                $product->increment('quantity', $quantity);
            } else {
                $product->decrement('quantity', $quantity);
            }
        }

        StockMovement::create([
            'product_id' => $product->id,
            'change_type' => $validated['change_type'],
            'quantity' => $quantity,
            'reason' => $validated['reason'],
            'source' => 'adjustment',
            'reference_type' => 'stock_adjustment',
            'reference_id' => null,
            'user_id' => $this->currentUser()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $product->refresh();
        if ($validated['change_type'] === 'decrease' && $product->isLowStock()) {
            return redirect()->route('stock.adjustments.index')
                ->with('success', 'Stock adjusted successfully')
                ->with('low_stock_warning', "Low stock alert: \"{$product->name}\" has only {$product->quantity} unit(s) remaining (threshold: {$product->low_stock_threshold}).");
        }

        return redirect()->route('stock.adjustments.index')->with('success', 'Stock adjusted successfully');
    }

    private function adjustIngredient(array $validated)
    {
        $ingredient = Ingredient::find($validated['ingredient_id']);

        if ($validated['change_type'] === 'decrease' && (float) $ingredient->quantity < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Not enough quantity available']);
        }

        if ($validated['change_type'] === 'increase') {
            $ingredient->increment('quantity', $validated['quantity']);
        } else {
            $ingredient->decrement('quantity', $validated['quantity']);
        }

        IngredientStockMovement::create([
            'ingredient_id' => $ingredient->id,
            'change_type' => $validated['change_type'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'source' => 'adjustment',
            'reference_type' => 'ingredient_adjustment',
            'reference_id' => null,
            'user_id' => $this->currentUser()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $ingredient->refresh();
        if ($validated['change_type'] === 'decrease' && $ingredient->isLowStock()) {
            return redirect()->route('stock.adjustments.index')
                ->with('success', 'Stock adjusted successfully')
                ->with('low_stock_warning', "Low stock alert: \"{$ingredient->name}\" has only {$ingredient->quantity} {$ingredient->unit} remaining (threshold: {$ingredient->low_stock_threshold}).");
        }

        return redirect()->route('stock.adjustments.index')->with('success', 'Stock adjusted successfully');
    }
}
