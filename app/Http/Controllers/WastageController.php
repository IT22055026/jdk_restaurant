<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class WastageController extends Controller
{
    public function index()
    {
        $wastages = Wastage::with('product')->paginate(10);
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.wastages-list', [
            'wastages' => $wastages,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        $products = Product::where('status', 'active')->get();
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.wastages-create', [
            'products' => $products,
            'modules' => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'date' => 'required|date',
        ]);

        $product = Product::find($validated['product_id']);
        if (!$product->is_unlimited_stock && $product->quantity < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Not enough quantity available']);
        }

        if (!$product->is_unlimited_stock) {
            $product->decrement('quantity', $validated['quantity']);
        }

        $wastage = Wastage::create($validated);
        $this->logStockMovement(
            $product,
            'decrease',
            $validated['quantity'],
            $validated['reason'],
            $validated['notes'] ?? null,
            $wastage->id
        );

        $product->refresh();
        if ($product->isLowStock()) {
            return redirect()->route('wastage.index')
                ->with('success', 'Wastage recorded successfully')
                ->with('low_stock_warning', "Low stock alert: \"{$product->name}\" has only {$product->quantity} unit(s) remaining (threshold: {$product->low_stock_threshold}).");
        }

        return redirect()->route('wastage.index')->with('success', 'Wastage recorded successfully');
    }

    public function show(Wastage $wastage)
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.wastages-show', [
            'wastage' => $wastage,
            'modules' => $modules,
        ]);
    }

    public function edit(Wastage $wastage)
    {
        $products = Product::where('status', 'active')->get();
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.wastages-edit', [
            'wastage' => $wastage,
            'products' => $products,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Wastage $wastage)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'date' => 'required|date',
        ]);

        $product = Product::find($validated['product_id']);
        $oldProduct = Product::find($wastage->product_id);

        if ($product->id === $oldProduct->id) {
            $quantityDiff = $validated['quantity'] - $wastage->quantity;
            if ($quantityDiff > 0 && !$product->is_unlimited_stock && $product->quantity < $quantityDiff) {
                return back()->withErrors(['quantity' => 'Not enough quantity available']);
            }
            if ($quantityDiff !== 0) {
                if (!$product->is_unlimited_stock) {
                    if ($quantityDiff > 0) {
                        $product->decrement('quantity', $quantityDiff);
                    } else {
                        $product->increment('quantity', abs($quantityDiff));
                    }
                }

                $this->logStockMovement(
                    $product,
                    $quantityDiff > 0 ? 'decrease' : 'increase',
                    abs($quantityDiff),
                    $validated['reason'],
                    $validated['notes'] ?? null,
                    $wastage->id
                );
            }
        } else {
            if (!$oldProduct->is_unlimited_stock) {
                $oldProduct->increment('quantity', $wastage->quantity);
            }
            $this->logStockMovement(
                $oldProduct,
                'increase',
                $wastage->quantity,
                'Wastage moved to another product',
                $wastage->notes,
                $wastage->id
            );

            if (!$product->is_unlimited_stock && $product->quantity < $validated['quantity']) {
                return back()->withErrors(['quantity' => 'Not enough quantity available']);
            }
            if (!$product->is_unlimited_stock) {
                $product->decrement('quantity', $validated['quantity']);
            }

            $this->logStockMovement(
                $product,
                'decrease',
                $validated['quantity'],
                $validated['reason'],
                $validated['notes'] ?? null,
                $wastage->id
            );
        }

        $wastage->update($validated);
        return redirect()->route('wastage.index')->with('success', 'Wastage updated successfully');
    }

    public function destroy(Wastage $wastage)
    {
        $product = $wastage->product;
        if (!$product->is_unlimited_stock) {
            $product->increment('quantity', $wastage->quantity);
        }

        $this->logStockMovement(
            $product,
            'increase',
            $wastage->quantity,
            'Wastage deleted: ' . $wastage->reason,
            $wastage->notes,
            $wastage->id
        );
        $wastage->delete();

        return redirect()->route('wastage.index')->with('success', 'Wastage deleted successfully');
    }

    private function logStockMovement(Product $product, string $changeType, int $quantity, ?string $reason, ?string $notes, int $wastageId): void
    {
        if ($quantity <= 0) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'change_type' => $changeType,
            'quantity' => $quantity,
            'reason' => $reason,
            'source' => 'wastage',
            'reference_type' => 'wastage',
            'reference_id' => $wastageId,
            'user_id' => $this->currentUser()->id,
            'notes' => $notes,
        ]);
    }
}
