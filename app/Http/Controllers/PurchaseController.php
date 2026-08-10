<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientStockMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['category.parent', 'supplier', 'product', 'ingredient']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->input('ingredient_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->input('to'));
        }

        $purchases = (clone $query)->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();
        $totalPurchases = (clone $query)->sum('amount');

        [$monthStart, $monthEnd] = BusinessDay::monthBoundsFor(BusinessDay::today());
        $thisMonthPurchases = Purchase::whereBetween('purchase_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])->sum('amount');
        $todayPurchases = Purchase::whereDate('purchase_date', BusinessDay::today()->format('Y-m-d'))->sum('amount');

        // Supplier totals for a chosen month — defaults to the current month.
        $summaryMonth = $request->input('summary_month', BusinessDay::today()->format('Y-m'));
        try {
            $summaryDate = Carbon::createFromFormat('Y-m-d', $summaryMonth . '-01');
        } catch (\Exception) {
            $summaryDate = BusinessDay::today();
            $summaryMonth = $summaryDate->format('Y-m');
        }
        [$summaryStart, $summaryEnd] = BusinessDay::monthBoundsFor($summaryDate);

        $supplierTotals = Purchase::selectRaw('supplier_id, SUM(amount) as total, COUNT(*) as purchase_count')
            ->whereNotNull('supplier_id')
            ->whereBetween('purchase_date', [$summaryStart->format('Y-m-d'), $summaryEnd->format('Y-m-d')])
            ->groupBy('supplier_id')
            ->with('supplier')
            ->orderByDesc('total')
            ->get();

        [$suppliers, $finishedGoods, $includedItems] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();

        return view('modules.purchases.index', compact(
            'purchases',
            'suppliers',
            'finishedGoods',
            'includedItems',
            'modules',
            'totalPurchases',
            'thisMonthPurchases',
            'todayPurchases',
            'supplierTotals',
            'summaryMonth'
        ));
    }

    public function create()
    {
        [$suppliers, $finishedGoods, $includedItems] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();
        $paymentMethods = Purchase::PAYMENT_METHODS;

        return view('modules.purchases.create', compact('suppliers', 'finishedGoods', 'includedItems', 'modules', 'paymentMethods'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(Purchase::PAYMENT_METHODS)),
            'supplier_invoice_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        $rows = $this->resolveItems($request->input('items', []), (int) $data['supplier_id']);

        $reference = 'PO-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        $userId = auth()->id();

        DB::transaction(function () use ($rows, $data, $reference, $userId) {
            foreach ($rows as $row) {
                $purchase = Purchase::create([
                    'purchase_category_id' => null,
                    'supplier_id' => $data['supplier_id'],
                    'product_id' => $row['product_id'],
                    'ingredient_id' => $row['ingredient_id'],
                    'user_id' => $userId,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'],
                    'unit_price' => $row['unit_price'],
                    'amount' => $row['amount'],
                    'purchase_date' => $data['purchase_date'],
                    'reference_no' => $reference,
                    'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                    'payment_method' => $data['payment_method'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->applyStockDelta($row['stock_target'], (float) $row['quantity'], $purchase, 'Stock purchase');
            }
        });

        $count = count($rows);
        $total = array_sum(array_column($rows, 'amount'));

        return redirect()->route('purchases.index')->with('success', $count === 1
            ? 'Purchase recorded successfully.'
            : "{$count} items recorded for this purchase (Ref: {$reference}) — total LKR " . number_format($total, 2) . '.');
    }

    public function edit(Purchase $purchase)
    {
        [$suppliers] = $this->formOptions();
        $modules = $this->currentUser()->role->modules()->get();
        $paymentMethods = Purchase::PAYMENT_METHODS;

        return view('modules.purchases.edit', compact('purchase', 'suppliers', 'modules', 'paymentMethods'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $this->validateSingle($request, $purchase);

        $target = $this->stockTargetFor($purchase);
        $oldQuantity = (float) $purchase->quantity;

        $purchase->update($validated);

        if ($target) {
            $delta = (float) $validated['quantity'] - $oldQuantity;
            if ($delta !== 0.0) {
                $this->applyStockDelta($target, $delta, $purchase, 'Purchase updated');
            }
        }

        return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase)
    {
        $target = $this->stockTargetFor($purchase);
        if ($target) {
            $this->applyStockDelta($target, -1 * (float) $purchase->quantity, $purchase, 'Purchase deleted');
        }

        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
    }

    /**
     * Validates and normalizes the "add multiple items, then save" payload from the
     * create form into plain row arrays ready for Purchase::create(), one per item.
     * Every row must resolve to a catalog Finished Good or Included Item belonging to
     * the chosen supplier — there's no free-typed/manual item path anymore.
     * Throws with a per-row, per-field error key (items.<index>.<field>) so the
     * create page can point the user straight at the offending row.
     *
     * @return array<int, array{product_id: ?int, ingredient_id: ?int, item_name: string,
     *   unit: string, quantity: float, unit_price: ?float, amount: float, stock_target: Product|Ingredient}>
     */
    private function resolveItems(array $items, int $supplierId): array
    {
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $ingredientIds = collect($items)->pluck('ingredient_id')->filter()->unique()->values();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $ingredients = Ingredient::whereIn('id', $ingredientIds)->get()->keyBy('id');

        $resolved = [];

        foreach (array_values($items) as $index => $item) {
            $position = $index + 1;

            if (!is_array($item)) {
                throw ValidationException::withMessages(["items.{$index}" => "Row {$position}: malformed item data."]);
            }

            $type = $item['item_type'] ?? null;

            $quantity = is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : null;
            if ($quantity === null || $quantity <= 0) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => "Row {$position}: enter a valid quantity."]);
            }

            $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== '' && is_numeric($item['unit_price'])
                ? (float) $item['unit_price'] : null;

            $amount = is_numeric($item['amount'] ?? null) ? (float) $item['amount'] : null;
            if ($amount === null || $amount <= 0) {
                throw ValidationException::withMessages(["items.{$index}.amount" => "Row {$position}: enter a valid total amount."]);
            }

            $resolved[] = match ($type) {
                Purchase::TYPE_FINISHED_GOOD => $this->resolveFinishedGood($item, $index, $position, $products, $supplierId, $quantity, $unitPrice, $amount),
                Purchase::TYPE_INCLUDED_ITEM => $this->resolveIncludedItem($item, $index, $position, $ingredients, $supplierId, $quantity, $unitPrice, $amount),
                default => throw ValidationException::withMessages(["items.{$index}.item_type" => "Row {$position}: choose a Finished Good or an Included Item."]),
            };
        }

        return $resolved;
    }

    private function resolveFinishedGood(array $item, int $index, int $position, $products, int $supplierId, float $quantity, ?float $unitPrice, float $amount): array
    {
        $product = $products->get($item['product_id'] ?? null);
        if (!$product) {
            throw ValidationException::withMessages(["items.{$index}.product_id" => "Row {$position}: choose a valid finished good."]);
        }
        if ((int) $product->supplier_id !== $supplierId) {
            throw ValidationException::withMessages(["items.{$index}.product_id" => "Row {$position}: \"{$product->name}\" isn't supplied by the selected supplier."]);
        }
        if (floor($quantity) != $quantity) {
            throw ValidationException::withMessages(["items.{$index}.quantity" => "Row {$position}: finished goods are purchased in whole units."]);
        }

        return [
            'product_id' => $product->id,
            'ingredient_id' => null,
            'item_name' => $product->name,
            'unit' => 'pcs',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'stock_target' => $product,
        ];
    }

    private function resolveIncludedItem(array $item, int $index, int $position, $ingredients, int $supplierId, float $quantity, ?float $unitPrice, float $amount): array
    {
        $ingredient = $ingredients->get($item['ingredient_id'] ?? null);
        if (!$ingredient) {
            throw ValidationException::withMessages(["items.{$index}.ingredient_id" => "Row {$position}: choose a valid included item."]);
        }
        if ((int) $ingredient->supplier_id !== $supplierId) {
            throw ValidationException::withMessages(["items.{$index}.ingredient_id" => "Row {$position}: \"{$ingredient->name}\" isn't supplied by the selected supplier."]);
        }

        return [
            'product_id' => null,
            'ingredient_id' => $ingredient->id,
            'item_name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'stock_target' => $ingredient,
        ];
    }

    /**
     * The catalog item (and, for legacy rows, the category) a purchase line is linked
     * to is fixed at creation time and can't be changed here — only the numbers and
     * the surrounding purchase details (supplier, date, payment, notes) are editable.
     */
    private function validateSingle(Request $request, Purchase $purchase): array
    {
        $rules = [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0.01',
            'purchase_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(Purchase::PAYMENT_METHODS)),
            'supplier_invoice_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        if ($purchase->itemType() === Purchase::TYPE_FINISHED_GOOD && floor($validated['quantity']) != $validated['quantity']) {
            throw ValidationException::withMessages(['quantity' => 'Finished goods are purchased in whole units.']);
        }

        return $validated;
    }

    private function stockTargetFor(Purchase $purchase): Product|Ingredient|null
    {
        if ($purchase->product_id) {
            return $purchase->product;
        }
        if ($purchase->ingredient_id) {
            return $purchase->ingredient;
        }
        return null;
    }

    /**
     * Applies a stock change tied to a purchase (create: +quantity, edit: the delta,
     * delete: -quantity) and logs it alongside the existing Stock Adjustment trail.
     * Unlimited-stock products never carry a quantity, so they're skipped.
     */
    private function applyStockDelta(Product|Ingredient|null $target, float $delta, Purchase $purchase, string $reason): void
    {
        if (!$target || $delta === 0.0) {
            return;
        }

        $userId = auth()->id();
        $increasing = $delta > 0;

        if ($target instanceof Product) {
            if ($target->is_unlimited_stock) {
                return;
            }
            $qty = (int) round(abs($delta));
            if ($qty === 0) {
                return;
            }

            if ($increasing) {
                $target->increment('quantity', $qty);
            } else {
                $target->decrement('quantity', min($qty, max((int) $target->fresh()->quantity, 0)));
            }

            StockMovement::create([
                'product_id' => $target->id,
                'change_type' => $increasing ? 'increase' : 'decrease',
                'quantity' => $qty,
                'reason' => $reason,
                'source' => 'purchase',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'user_id' => $userId,
            ]);
            return;
        }

        $qty = round(abs($delta), 3);
        if ($qty <= 0) {
            return;
        }

        if ($increasing) {
            $target->increment('quantity', $qty);
        } else {
            $target->decrement('quantity', min($qty, max((float) $target->fresh()->quantity, 0)));
        }

        IngredientStockMovement::create([
            'ingredient_id' => $target->id,
            'change_type' => $increasing ? 'increase' : 'decrease',
            'quantity' => $qty,
            'reason' => $reason,
            'source' => 'purchase',
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'user_id' => $userId,
        ]);
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection} */
    private function formOptions(): array
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $finishedGoods = Product::where('is_finished_good', true)->where('status', 'active')
            ->whereNotNull('supplier_id')->orderBy('name')->get();
        $includedItems = Ingredient::where('status', 'active')
            ->whereNotNull('supplier_id')->orderBy('name')->get();

        return [$suppliers, $finishedGoods, $includedItems];
    }
}
