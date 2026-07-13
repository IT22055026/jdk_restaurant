<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $movements = StockMovement::with(['product', 'user'])->latest()->paginate(15);

        return view('modules.stock-adjustments-index', [
            'modules' => $modules,
            'movements' => $movements,
        ]);
    }

    public function create()
    {
        $modules = $this->currentUser()->role->modules()->get();
        $products = Product::orderBy('name')->get();

        return view('modules.stock-adjustments-create', [
            'modules' => $modules,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'change_type' => 'required|in:increase,decrease',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::find($validated['product_id']);
        if ($validated['change_type'] === 'decrease' && !$product->is_unlimited_stock && $product->quantity < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Not enough quantity available']);
        }

        if (!$product->is_unlimited_stock) {
            if ($validated['change_type'] === 'increase') {
                $product->increment('quantity', $validated['quantity']);
            } else {
                $product->decrement('quantity', $validated['quantity']);
            }
        }

        StockMovement::create([
            'product_id' => $product->id,
            'change_type' => $validated['change_type'],
            'quantity' => $validated['quantity'],
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
}
