<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientStockMovement;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\ShiftTransaction;
use App\Models\StockMovement;
use App\Services\IngredientStockService;
use App\Services\ProductStockService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::where('status', 'active')->orderBy('sort_order')->get();
        $products = Product::where('status', 'active')->get();
        $modules = $this->currentUser()->role->modules()->get();

        $activeShift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        return view('modules.pos', [
            'categories' => $categories,
            'products' => $products,
            'modules' => $modules,
            'activeShift' => $activeShift,
        ]);
    }

    public function getProducts(Request $request)
    {
        $query = Product::where('status', 'active');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('barcode', $search);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->with('ingredients')->get()->map(function ($product) {
            // Recipe-tracked items (not finished goods) may share included items with
            // other menu items — expose each item's requirement + current stock so the
            // POS screen can keep sibling products in sync as the cart fills up, without
            // waiting for a full product reload.
            $recipe = (!$product->is_unlimited_stock && !$product->is_finished_good)
                ? $product->ingredients->map(fn($ingredient) => [
                    'ingredient_id' => $ingredient->id,
                    'quantity_per_unit' => (float) $ingredient->pivot->quantity_per_unit,
                    'ingredient_stock' => (float) $ingredient->quantity,
                ])->values()
                : [];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) ($product->selling_price ?? $product->price),
                'cost_price' => (float) ($product->cost_price ?? 0),
                'category_id' => $product->category_id,
                'barcode' => $product->barcode,
                'is_unlimited_stock' => $product->is_unlimited_stock,
                'quantity' => $product->is_unlimited_stock ? 0 : $product->availableStock(),
                'image' => $product->image,
                'recipe' => $recipe,
            ];
        });

        return response()->json($products);
    }

    // Raw/included items (not sellable menu Products) — used by both the
    // "Include Items" section (buy one directly, e.g. "extra mayonnaise")
    // and the "Give a Free Item" picker (give one away).
    public function getIngredients()
    {
        $ingredients = Ingredient::where('status', 'active')->orderBy('name')->get();

        return response()->json($ingredients->map(fn($ingredient) => [
            'id' => $ingredient->id,
            'name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'quantity' => (float) $ingredient->quantity,
            'selling_price' => $ingredient->selling_price !== null ? (float) $ingredient->selling_price : null,
        ]));
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'waiter_name' => 'nullable|string',
            'order_type' => 'nullable|in:dine_in,takeaway',
        ]);

        // ORD-{business date}-{sequence, resets to 0001 each business day}.
        // The trading day runs 06:00-05:59 the next calendar day (see
        // BusinessDay), so an order placed at 1am still numbers under
        // yesterday's date/sequence, matching the token numbering it sits
        // alongside.
        $businessDate = BusinessDay::today()->toDateString();
        $order = null;

        // lockForUpdate() alone can't stop two simultaneous "first order of
        // the day" requests from both computing sequence 1 (there's no row
        // yet to lock), so the real guarantee is the (business_date,
        // daily_sequence) unique constraint — retry with the next number on
        // conflict rather than fail the request.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $order = DB::transaction(function () use ($validated, $businessDate) {
                    // whereDate(), not where() — the 'date' cast serializes
                    // business_date with a full datetime format when saving
                    // (e.g. "2026-07-30 00:00:00"), so a plain equality check
                    // against the bare date string would never match.
                    $nextSequence = (Order::whereDate('business_date', $businessDate)->lockForUpdate()->max('daily_sequence') ?? 0) + 1;

                    return Order::create([
                        'order_number' => 'ORD-' . str_replace('-', '', $businessDate) . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT),
                        'business_date' => $businessDate,
                        'daily_sequence' => $nextSequence,
                        'customer_id' => $validated['customer_id'] ?? null,
                        'customer_name' => $validated['customer_name'] ?? null,
                        'customer_phone' => $validated['customer_phone'] ?? null,
                        'user_id' => $this->currentUser()->id,
                        'waiter_name' => $validated['waiter_name'] ?? $this->currentUser()->name,
                        'order_type' => $validated['order_type'] ?? 'dine_in',
                    ]);
                });
                break;
            } catch (\PDOException $e) {
                // Illuminate\Database\QueryException extends PDOException, but
                // at least on SQLite a bare PDOException can propagate from
                // inside the transaction too — catch the parent class so both
                // are retried instead of only Laravel's wrapped form.
                if ($attempt === 5) {
                    throw $e;
                }
            }
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'order_type' => $order->order_type,
        ]);
    }

    public function updateToken(Request $request, Order $order)
    {
        $validated = $request->validate([
            'token_number' => 'required|integer|min:1',
        ]);

        // The trading day runs 06:00-05:59 the next calendar day (shifts go
        // overnight), so a token issued at, say, 1am still belongs to the
        // PREVIOUS business date, not the new calendar date.
        $tokenDate = $order->token_date ?? BusinessDay::today()->toDateString();

        // Tokens are physical tags handed to customers and returned once their
        // meal is done, so the same number is meant to be reused several times
        // a day — only block it while another order still holds it (i.e. that
        // order hasn't been completed or cancelled yet).
        $duplicate = Order::whereDate('token_date', $tokenDate)
            ->where('token_number', $validated['token_number'])
            ->where('id', '!=', $order->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => "Token #{$validated['token_number']} is still in use by an open order today",
            ], 422);
        }

        $order->update([
            'token_date' => $tokenDate,
            'token_number' => $validated['token_number'],
        ]);

        return response()->json([
            'success' => true,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date->toDateString(),
        ]);
    }

    public function getOrder(Order $order)
    {
        $order->load('items.product', 'customer');

        // Component lines (the individual products/ingredients an offer bundles)
        // are hidden here — the cashier only needs to see the one offer billing
        // line. They still exist for printToken()/stock deduction purposes.
        $itemsData = $order->items->where('is_offer_component', false)->values()->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'offer_id' => $item->offer_id,
                'product_name' => $item->product_name,
                'unit_price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'discount_percent' => (float) $item->discount_percent,
                'kitchen_notes' => $item->kitchen_notes,
                'is_bar_item' => (bool) $item->is_bar_item,
                'kot_printed' => (bool) $item->kot_printed,
                'image' => $item->product?->image,
            ];
        });

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'status' => $order->status,
            'order_type' => $order->order_type ?? 'dine_in',
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'live_bill_enabled' => $order->live_bill_enabled,
            'waiter_bill_printed_at' => $order->waiter_bill_printed_at,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax_amount' => (float) $order->tax_amount,
            'total' => (float) $order->total,
            'items' => $itemsData,
        ]);
    }

    /**
     * Update the order type (dine_in / takeaway) for an open order.
     * Called by the POS dropdown whenever the cashier switches it.
     */
    public function updateOrderType(Request $request, Order $order)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change order type on a completed or cancelled bill',
            ], 422);
        }

        $validated = $request->validate([
            'order_type' => 'required|in:dine_in,takeaway',
        ]);

        $order->update(['order_type' => $validated['order_type']]);

        return response()->json([
            'success' => true,
            'order_type' => $order->order_type,
        ]);
    }

    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|required_without:ingredient_id|exists:products,id',
            'ingredient_id' => 'nullable|required_without:product_id|exists:ingredients,id',
            'quantity' => 'required|integer|min:1',
            'kitchen_notes' => 'nullable|string',
            'is_bar_item' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
        ]);

        // Given away as a discount (e.g. a free sauce cup) rather than a normal
        // sale: the item still goes to the kitchen and deducts stock like any
        // other line, but is 100% off so it shows as FREE on the bill and
        // contributes nothing to the total, instead of shaving money off the
        // order the way a percentage/fixed discount would.
        $isFree = $validated['is_free'] ?? false;

        if (!empty($validated['ingredient_id'])) {
            // A raw/included item — e.g. an extra sauce pot — added directly
            // rather than through one of the sellable menu Products.
            // IngredientStockService already knows how to deduct stock for
            // any order_item carrying an ingredient_id (the same path offer
            // components use), so nothing extra is needed for that part.
            $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
            $name = $ingredient->name;

            if ($isFree) {
                // Given away — the "price" is only shown crossed-out on the
                // receipt, so use the cost basis rather than a customer price.
                $price = (float) ($ingredient->cost_per_unit ?? 0);
            } else {
                // A customer is actually paying for this one directly (e.g.
                // "extra mayonnaise"), so it needs a real selling price.
                if (!$ingredient->selling_price || $ingredient->selling_price <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "{$ingredient->name} doesn't have a selling price set yet — add one in Included Items first, or give it away as a free item instead.",
                    ], 422);
                }
                $price = (float) $ingredient->selling_price;
            }

            $matchColumn = 'ingredient_id';
            $matchValue = $ingredient->id;
            $extraFields = ['ingredient_id' => $ingredient->id, 'offer_component_qty' => 1];
        } else {
            $product = Product::find($validated['product_id']);
            $name = $product->name;
            $price = $product->selling_price ?? $product->price;
            $matchColumn = 'product_id';
            $matchValue = $product->id;
            $extraFields = ['product_id' => $product->id];
        }

        // Find any existing item for this product/ingredient in the order
        // (excluding hidden offer-component rows, which must stay tied to
        // their offer's quantity). A free-item add only merges into an
        // existing 100%-off line, and a normal add only merges into a
        // non-100%-off one — they must never merge into each other, or a
        // paid re-add would silently fold into (and zero out) a free line,
        // or vice versa.
        $existingItem = OrderItem::where('order_id', $order->id)
            ->where($matchColumn, $matchValue)
            ->whereNull('offer_id')
            ->where('discount_percent', $isFree ? '=' : '!=', 100)
            ->first();

        if ($existingItem) {
            // If item exists, increase the quantity
            $existingItem->quantity += $validated['quantity'];
            $existingItem->subtotal = $existingItem->unit_price * $existingItem->quantity * (1 - $existingItem->discount_percent / 100);
            $existingItem->save();
            $item = $existingItem;
        } else {
            // Create first line item
            $item = OrderItem::create(array_merge([
                'order_id' => $order->id,
                'product_name' => $name,
                'unit_price' => $price,
                'quantity' => $validated['quantity'],
                'discount_percent' => $isFree ? 100 : 0,
                'subtotal' => $isFree ? 0 : $price * $validated['quantity'],
                'kitchen_notes' => $validated['kitchen_notes'] ?? null,
                'is_bar_item' => $validated['is_bar_item'] ?? false,
                'kot_printed' => false,
                'printed_qty' => 0,
            ], $extraFields));
        }

        // Ingredient stock is no longer touched here — it's only deducted once the
        // item is actually confirmed to the kitchen (see printToken()).
        $this->updateOrderTotals($order);

        return response()->json([
            'success' => true,
            'item_id' => $item->id,
            'item_kot_printed' => (bool) $item->kot_printed,
            'message' => $name . ' added to order',
        ]);
    }

    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        // Nothing to restore: ingredient stock is only committed once the item is
        // confirmed to the kitchen via printToken(), and removing an already-printed
        // item doesn't un-cook it.
        if ($item->offer_id && !$item->is_offer_component) {
            // This is an offer's visible billing line — remove it and every hidden
            // product/ingredient component line that came bundled with it.
            OrderItem::where('order_id', $order->id)->where('offer_id', $item->offer_id)->delete();
        } else {
            $item->delete();
        }

        $this->updateOrderTotals($order);

        return response()->json(['success' => true, 'message' => 'Item removed']);
    }

    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'kitchen_notes' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Quantity increases beyond what's already been printed are picked up as a
        // delta the next time printToken() runs; decreases below printed_qty don't
        // refund ingredients since that portion may already be cooking.
        $discountPercent = $validated['discount_percent'] ?? $item->discount_percent;
        $subtotal = $item->unit_price * $validated['quantity'] * (1 - $discountPercent / 100);

        $item->update([
            'quantity' => $validated['quantity'],
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'kitchen_notes' => $validated['kitchen_notes'] ?? $item->kitchen_notes,
        ]);

        if ($item->offer_id && !$item->is_offer_component) {
            // Keep every hidden component line (one unit per linked product/ingredient
            // per offer) in lockstep with the billing line's quantity.
            OrderItem::where('order_id', $order->id)
                ->where('offer_id', $item->offer_id)
                ->where('is_offer_component', true)
                ->update(['quantity' => $validated['quantity'], 'subtotal' => 0]);
        }

        $this->updateOrderTotals($order);

        return response()->json(['success' => true, 'message' => 'Item updated']);
    }

    public function completeOrder(Request $request, Order $order)
    {
        if (!$order->token_number) {
            return response()->json([
                'success' => false,
                'message' => 'Enter the token number before completing the bill',
            ], 422);
        }

        $validated = $request->validate([
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $subtotal = $order->items->sum('subtotal');
        $discount = 0;

        if ($validated['discount_type'] === 'percentage') {
            $discount = ($subtotal * $validated['discount_value']) / 100;
        } elseif ($validated['discount_type'] === 'fixed') {
            $discount = $validated['discount_value'];
        }

        $total = $subtotal - $discount;

        $order->update([
            'status' => 'completed',
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => 0,
            'total' => $total,
            'printed_at' => now(),
            'kot_printed_at' => $order->kot_printed_at ?? ($order->items->where('is_bar_item', false)->count() > 0 ? now() : null),
        ]);

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $subtotal,
            'discount_amount' => (float) $discount,
            'total' => (float) $total,
            'items' => $order->items->where('is_offer_component', false)->values()->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
        ]);
    }

    public function getActiveOffers()
    {
        $offers = Offer::where('is_active', true)->with('ingredients')->get();

        return response()->json($offers->map(fn($offer) => [
            'id' => $offer->id,
            'name' => $offer->name,
            'description' => $offer->description,
            'image' => $offer->image,
            'price' => (float) $offer->price,
            'includes' => $offer->ingredients->pluck('name')->values(),
        ]));
    }

    public function addOffer(Request $request, Order $order)
    {
        $validated = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $quantity = $validated['quantity'] ?? 1;

        $billingLine = OrderItem::where('order_id', $order->id)
            ->where('offer_id', $validated['offer_id'])
            ->where('is_offer_component', false)
            ->first();

        if ($billingLine) {
            $newQuantity = $billingLine->quantity + $quantity;
            $billingLine->update([
                'quantity' => $newQuantity,
                'subtotal' => $billingLine->unit_price * $newQuantity,
            ]);

            OrderItem::where('order_id', $order->id)
                ->where('offer_id', $validated['offer_id'])
                ->where('is_offer_component', true)
                ->update(['quantity' => $newQuantity]);
        } else {
            $offer = Offer::with('ingredients')->findOrFail($validated['offer_id']);

            OrderItem::create([
                'order_id' => $order->id,
                'offer_id' => $offer->id,
                'product_name' => $offer->name,
                'unit_price' => $offer->price,
                'quantity' => $quantity,
                'subtotal' => $offer->price * $quantity,
                'is_bar_item' => true, // keeps the billing line off the kitchen ticket
                'is_offer_component' => false,
            ]);

            foreach ($offer->ingredients as $ingredient) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'offer_id' => $offer->id,
                    'ingredient_id' => $ingredient->id,
                    'product_name' => $ingredient->name,
                    'unit_price' => 0,
                    'quantity' => $quantity,
                    'subtotal' => 0,
                    'is_bar_item' => false,
                    'is_offer_component' => true,
                    // Snapshot the offer's per-unit amount now, so later edits to the
                    // offer's recipe don't retroactively change this already-placed order.
                    'offer_component_qty' => $ingredient->pivot->quantity,
                ]);
            }
        }

        $this->updateOrderTotals($order);

        return response()->json(['success' => true, 'message' => 'Offer added to order']);
    }

    public function salesReport()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.sales-report', ['modules' => $modules]);
    }

    public function getSalesReport(Request $request)
    {
        $query = Order::with('discardedBy', 'items')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Business dates, not calendar dates — an order at 1am still belongs
        // to the previous day's trading window (see App\Support\BusinessDay).
        if ($request->filled('date_from') && $request->filled('date_to')) {
            [$start, $end] = BusinessDay::boundsBetween($request->input('date_from'), $request->input('date_to'));
            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($request->filled('date_from')) {
            [$start] = BusinessDay::boundsFor($request->input('date_from'));
            $query->where('created_at', '>=', $start);
        } elseif ($request->filled('date_to')) {
            [, $end] = BusinessDay::boundsFor($request->input('date_to'));
            $query->where('created_at', '<=', $end);
        }

        $orders = $query->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'customer_name' => $order->customer_name ?? 'Walk-in',
            'status' => $order->status,
            'order_type' => $order->order_type ?? 'dine_in',
            'items_count' => $order->items->where('is_offer_component', false)->count(),
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'discard_reason' => $order->discard_reason,
            'discarded_by' => $order->discardedBy->name ?? null,
            'discarded_at' => $order->discarded_at?->format('M d, Y H:i'),
            'created_at' => $order->created_at->format('M d, Y H:i'),
        ]));
    }

    /**
     * Reverse a completed (paid) bill: restore stock for whatever was actually
     * deducted at pay time, and flip the order to cancelled with an audit trail
     * — the same discard_reason/discarded_by/discarded_at fields a pre-payment
     * discard uses. The sales report / shift totals both compute revenue by
     * summing orders with status=completed, so this alone removes the bill's
     * money from every report without any separate ledger entry needed.
     */
    public function revokeOrder(Request $request, Order $order)
    {
        if ($order->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed bills can be revoked',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order->load('items.product.ingredients');
        $userId = $this->currentUser()->id;
        $reason = $validated['reason'];

        DB::transaction(function () use ($order, $userId, $reason) {
            foreach ($order->items->where('is_bar_item', false) as $item) {
                if ($item->printed_qty <= 0) {
                    continue;
                }

                if ($item->ingredient_id) {
                    $qty = (float) ($item->offer_component_qty ?? 1) * $item->printed_qty;
                    Ingredient::find($item->ingredient_id)?->increment('quantity', $qty);
                    IngredientStockMovement::create([
                        'ingredient_id' => $item->ingredient_id,
                        'change_type' => 'increase',
                        'quantity' => $qty,
                        'reason' => 'Bill revoked',
                        'source' => 'revoke',
                        'reference_type' => OrderItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $userId,
                    ]);
                    continue;
                }

                $product = $item->product;
                if (!$product || $product->is_unlimited_stock) {
                    continue;
                }

                if ($product->is_finished_good) {
                    $product->increment('quantity', $item->printed_qty);
                    StockMovement::create([
                        'product_id' => $product->id,
                        'change_type' => 'increase',
                        'quantity' => $item->printed_qty,
                        'reason' => 'Bill revoked',
                        'source' => 'revoke',
                        'reference_type' => OrderItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $userId,
                    ]);
                } else {
                    foreach ($product->ingredients as $ingredient) {
                        $qty = (float) $ingredient->pivot->quantity_per_unit * $item->printed_qty;
                        if ($qty <= 0) {
                            continue;
                        }
                        $ingredient->increment('quantity', $qty);
                        IngredientStockMovement::create([
                            'ingredient_id' => $ingredient->id,
                            'change_type' => 'increase',
                            'quantity' => $qty,
                            'reason' => 'Bill revoked',
                            'source' => 'revoke',
                            'reference_type' => OrderItem::class,
                            'reference_id' => $item->id,
                            'user_id' => $userId,
                        ]);
                    }
                }
            }

            $order->update([
                'status' => 'cancelled',
                'discard_reason' => $reason,
                'discarded_by' => $userId,
                'discarded_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Bill revoked']);
    }

    private function updateOrderTotals(Order $order)
    {
        $subtotal = $order->items->sum('subtotal');
        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total' => $subtotal,
        ]);
    }

    public function updateCustomer(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        $order->update([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer details updated',
        ]);
    }

    public function printWaiterBill(Order $order)
    {
        $order->load('items');
        $order->update(['waiter_bill_printed_at' => now()]);

        $subtotal = $order->items->sum('subtotal');
        $total = $subtotal;

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $subtotal,
            'tax_amount' => 0,
            'total' => (float) $total,
            'items' => $order->items->where('is_offer_component', false)->values()->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'discount_percent' => (float) $item->discount_percent,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
        ]);
    }

    public function toggleLiveBill(Request $request, Order $order)
    {
        $order->update(['live_bill_enabled' => !$order->live_bill_enabled]);

        return response()->json([
            'success' => true,
            'live_bill_enabled' => $order->live_bill_enabled,
            'message' => $order->live_bill_enabled ? 'Live bill enabled' : 'Live bill disabled',
        ]);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only open bills can be discarded',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // No ingredient stock to restore: unprinted items never consumed any, and
        // printed items already went to the kitchen, so their ingredients stay spent.
        $order->update([
            'status' => 'cancelled',
            'discard_reason' => $validated['reason'],
            'discarded_by' => $this->currentUser()->id,
            'discarded_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Bill discarded']);
    }

    /**
     * Park an in-progress bill without paying or discarding it — items, token
     * number and totals are left untouched so it can be resumed later from
     * the Sales Report page's "Resume & Pay" action (see getOrder()/the POS
     * screen's resumeExistingOrder()), which already accepts any non-final
     * status.
     */
    public function holdOrder(Request $request, Order $order)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only open bills can be held',
            ], 422);
        }

        $order->update(['status' => 'hold']);

        return response()->json(['success' => true, 'message' => 'Bill held']);
    }

    public function payOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,mixed,pickme,uber',
            'amount_paid' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'order_type' => 'nullable|in:dine_in,takeaway',
        ]);

        // PickMe/Uber orders are delivery orders with no physical token handed
        // out at the counter, so the manual token-number requirement doesn't
        // apply to them — every other payment method still needs one.
        $tokenExempt = in_array($validated['payment_method'], ['pickme', 'uber']);

        if (!$order->token_number && !$tokenExempt) {
            return response()->json([
                'success' => false,
                'message' => 'Enter the token number before completing the bill',
            ], 422);
        }

        $order->load('items.product.ingredients');

        // There's no separate "print token" step anymore — tokens are handed out
        // physically before billing, so paying is what confirms items to the
        // kitchen and deducts stock, all in one action.
        $kitchenItems = $order->items
            ->where('is_bar_item', false)
            ->filter(fn($item) => $item->quantity > $item->printed_qty)
            ->values();

        $kotItems = [];
        if ($kitchenItems->isNotEmpty()) {
            $itemDeltas = $kitchenItems->map(fn($item) => [
                'item' => $item,
                'delta' => $item->quantity - $item->printed_qty,
            ])->all();

            $userId = $this->currentUser()->id;

            try {
                DB::transaction(function () use ($itemDeltas, $userId) {
                    app(ProductStockService::class)->deductForToken($itemDeltas, $userId);
                    app(IngredientStockService::class)->deductForToken($itemDeltas, $userId);
                });
            } catch (InsufficientStockException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            foreach ($kitchenItems as $item) {
                $delta = $item->quantity - $item->printed_qty;

                $kotItems[] = [
                    'product_name' => $item->product_name,
                    // For an offer's included-item component, show how much of that
                    // item is actually needed (its per-offer rate × how many offers
                    // sold) rather than the order line's own quantity, which only
                    // counts how many offers were bought.
                    'quantity' => $delta * (float) ($item->offer_component_qty ?? 1),
                    'kitchen_notes' => $item->kitchen_notes,
                ];

                $item->update(['kot_printed' => true, 'printed_qty' => $item->quantity]);
            }
        }

        $subtotal = $order->items->sum('subtotal');
        $discount = 0;

        if (($validated['discount_type'] ?? null) === 'percentage') {
            $discount = ($subtotal * $validated['discount_value']) / 100;
        } elseif (($validated['discount_type'] ?? null) === 'fixed') {
            $discount = $validated['discount_value'];
        }

        $total = $subtotal - $discount;
        $amountPaid = $validated['amount_paid'];
        $change = max(0, $amountPaid - $total);

        $order->update([
            'status' => 'completed',
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => 0,
            'total' => $total,
            'payment_method' => $validated['payment_method'],
            'amount_paid' => $amountPaid,
            'change_amount' => $change,
            'printed_at' => now(),
            'kot_printed_at' => $order->kot_printed_at ?? (count($kotItems) > 0 ? now() : null),
            'order_type' => $validated['order_type'] ?? ($order->order_type ?? 'dine_in'),
        ]);

        // Record transaction in active shift
        $activeShift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if ($activeShift) {
            ShiftTransaction::create([
                'shift_id' => $activeShift->id,
                'order_id' => $order->id,
                'transaction_type' => 'sale',
                'amount' => $total,
                'payment_method' => $validated['payment_method'],
                'description' => "Order #{$order->order_number}",
            ]);

            if ($discount > 0) {
                ShiftTransaction::create([
                    'shift_id' => $activeShift->id,
                    'order_id' => $order->id,
                    'transaction_type' => 'discount',
                    'amount' => $discount,
                    'description' => "Discount on Order #{$order->order_number}",
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'order_type' => $order->order_type ?? 'dine_in',
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $subtotal,
            'discount_amount' => (float) $discount,
            'total' => (float) $total,
            'payment_method' => $validated['payment_method'],
            'amount_paid' => (float) $amountPaid,
            'change_amount' => (float) $change,
            'items' => $order->items->where('is_offer_component', false)->values()->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'discount_percent' => (float) $item->discount_percent,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
            'kot_items' => $kotItems,
        ]);
    }

    public function orderHistory()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.order-history', ['modules' => $modules]);
    }

    public function getOrderHistory(Request $request)
    {
        $query = Order::with('items')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%");
        }

        $orders = $query->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'customer_name' => $order->customer_name ?? 'Walk-in',
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'items_count' => $order->items->count(),
            'created_at' => $order->created_at->format('M d, Y H:i'),
            'printed_at' => $order->printed_at?->format('M d, Y H:i'),
        ]), 200);
    }

    public function tokenHistory()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.token-history', ['modules' => $modules]);
    }

    public function getTokenHistory(Request $request)
    {
        $query = Order::with('items')
            ->whereNotNull('kot_printed_at')
            ->orderBy('kot_printed_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'customer_name' => $order->customer_name ?? 'N/A',
            'items_count' => $order->items->count(),
            'token_printed_at' => $order->kot_printed_at?->format('M d, Y H:i'),
            'bot_printed_at' => $order->bot_printed_at?->format('M d, Y H:i'),
            'items' => $order->items->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'kitchen_notes' => $item->kitchen_notes,
                'is_bar_item' => (bool) $item->is_bar_item,
            ]),
        ]), 200);
    }

    public function reprintReceipt(Order $order)
    {
        $order->load('items');

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax_amount' => (float) $order->tax_amount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'amount_paid' => (float) $order->amount_paid,
            'change_amount' => (float) $order->change_amount,
            'printed_at' => $order->printed_at?->format('M d, Y H:i'),
            'items' => $order->items->where('is_offer_component', false)->values()->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'discount_percent' => (float) $item->discount_percent,
            ]),
        ]);
    }

    public function reprintToken(Order $order)
    {
        $order->load('items');
        $kitchenItems = $order->items->where('is_bar_item', false)->values();

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'customer_name' => $order->customer_name,
            'date_time' => now()->format('M d, Y H:i'),
            'is_reprint' => true,
            'items' => $kitchenItems->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
        ]);
    }
}
