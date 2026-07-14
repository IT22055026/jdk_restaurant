<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\ShiftTransaction;
use App\Services\IngredientStockService;
use App\Services\ProductStockService;
use App\Services\TokenNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function getOpenTokens()
    {
        $orders = Order::whereIn('status', ['pending', 'confirmed', 'hold'])
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'items_count' => $order->items->count(),
            'total' => (float) $order->total,
            'created_at' => $order->created_at->toIso8601String(),
        ]));
    }

    public function findOrderByToken(Request $request, int $tokenNumber)
    {
        $order = Order::where('token_number', $tokenNumber)
            ->where('token_date', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed', 'hold'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'No open order with that token today',
            ], 404);
        }

        return response()->json(['success' => true, 'order_id' => $order->id]);
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
            ];
        });

        return response()->json($products);
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'waiter_name' => 'nullable|string',
        ]);

        $token = app(TokenNumberService::class)->next();

        $order = Order::create([
            'order_number' => (string) Str::uuid(), // temp unique placeholder, replaced below once the id is known
            'token_date' => $token['date'],
            'token_number' => $token['number'],
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'user_id' => $this->currentUser()->id,
            'waiter_name' => $validated['waiter_name'] ?? $this->currentUser()->name,
        ]);

        // Sequential, human-readable order number derived from the auto-increment id
        // (guaranteed unique by the DB) instead of a random string.
        $order->update(['order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)]);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date->toDateString(),
        ]);
    }

    public function getOrder(Order $order)
    {
        $order->load('items.product', 'customer');

        $itemsData = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
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

    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'kitchen_notes' => 'nullable|string',
            'is_bar_item' => 'nullable|boolean',
        ]);

        $product = Product::find($validated['product_id']);
        $price = $product->selling_price ?? $product->price;

        // Find any existing item for this product in the order
        $existingItem = OrderItem::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            // If item exists, increase the quantity
            $existingItem->quantity += $validated['quantity'];
            $existingItem->subtotal = $existingItem->unit_price * $existingItem->quantity;
            $existingItem->save();
            $item = $existingItem;
        } else {
            // Create first line item
            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $price,
                'quantity' => $validated['quantity'],
                'subtotal' => $price * $validated['quantity'],
                'kitchen_notes' => $validated['kitchen_notes'] ?? null,
                'is_bar_item' => $validated['is_bar_item'] ?? false,
                'kot_printed' => false,
                'printed_qty' => 0,
            ]);
        }

        // Ingredient stock is no longer touched here — it's only deducted once the
        // item is actually confirmed to the kitchen (see printToken()).
        $this->updateOrderTotals($order);

        return response()->json([
            'success' => true,
            'item_id' => $item->id,
            'item_kot_printed' => (bool) $item->kot_printed,
            'message' => $product->name . ' added to order',
        ]);
    }

    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        // Nothing to restore: ingredient stock is only committed once the item is
        // confirmed to the kitchen via printToken(), and removing an already-printed
        // item doesn't un-cook it.
        $item->delete();
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

        $this->updateOrderTotals($order);

        return response()->json(['success' => true, 'message' => 'Item updated']);
    }

    public function holdOrder(Order $order)
    {
        $order->update(['status' => 'hold']);
        return response()->json(['success' => true, 'message' => 'Order held']);
    }

    public function completeOrder(Request $request, Order $order)
    {
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
            'items' => $order->items->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
        ]);
    }

    public function printToken(Order $order)
    {
        $order->load('items.product.ingredients');

        // Get kitchen items (not bar items) that have quantity > printed_qty
        $printableItems = $order->items
            ->where('is_bar_item', false)
            ->filter(function ($item) {
                return $item->quantity > $item->printed_qty;
            })
            ->values();

        if ($printableItems->isEmpty()) {
            // If the order already has kitchen items printed but kot_printed_at is missing, fix it now
            if (!$order->kot_printed_at && $order->items->where('is_bar_item', false)->count() > 0) {
                $order->update(['kot_printed_at' => now()]);
                return response()->json([
                    'success' => true,
                    'message' => 'Token already printed, history record updated.',
                    'order_number' => $order->order_number,
                    'token_number' => $order->token_number,
                    'items' => []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No new items to print. All items already sent to kitchen.',
                'order_number' => $order->order_number,
                'token_number' => $order->token_number,
            ], 422);
        }

        // Deduct stock for the delta being newly confirmed to the kitchen. Finished
        // goods (e.g. bottled drinks) deduct their own quantity; recipe-tracked dishes
        // deduct ingredients instead. Both run inside one transaction so a shortfall in
        // either never leaves the other half-deducted.
        $itemDeltas = $printableItems->map(function ($item) {
            return ['item' => $item, 'delta' => $item->quantity - $item->printed_qty];
        })->all();

        $userId = $this->currentUser()->id;

        try {
            $productChanges = [];
            $ingredientChanges = [];
            DB::transaction(function () use ($itemDeltas, $userId, &$productChanges, &$ingredientChanges) {
                $productChanges = app(ProductStockService::class)->deductForToken($itemDeltas, $userId);
                $ingredientChanges = app(IngredientStockService::class)->deductForToken($itemDeltas, $userId);
            });
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'order_number' => $order->order_number,
                'token_number' => $order->token_number,
            ], 422);
        }

        // Prepare items to print and update printed_qty
        $itemsToPrint = [];
        foreach ($printableItems as $item) {
            $qtyToPrint = $item->quantity - $item->printed_qty;

            $itemsToPrint[] = [
                'product_id' => $item->product_id,
                'is_finished_good' => $item->product?->is_finished_good ? true : false,
                'product_name' => $item->product_name,
                'quantity' => $qtyToPrint,
                'kitchen_notes' => $item->kitchen_notes,
            ];

            // Mark as printed and update printed_qty
            $item->update([
                'kot_printed' => true,
                'printed_qty' => $item->quantity,
                'updated_at' => now(),
            ]);
        }

        $order->update(['kot_printed_at' => now()]);

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'token_date' => $order->token_date?->toDateString(),
            'items' => $itemsToPrint,
            'product_changes' => $productChanges ?? [],
            'ingredient_changes' => $ingredientChanges ?? [],
        ]);
    }

    public function getHeldOrders()
    {
        $orders = Order::where('status', 'hold')->with('items')->latest()->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'token_number' => $order->token_number,
            'items_count' => $order->items->count(),
            'total' => (float) $order->total,
        ]));
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
            'items' => $order->items->map(fn($item) => [
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

    public function cancelOrder(Order $order)
    {
        // No ingredient stock to restore: unprinted items never consumed any, and
        // printed items already went to the kitchen, so their ingredients stay spent.
        $order->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Order cancelled']);
    }

    public function payOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,mixed',
            'amount_paid' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $order->load('items');
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
            'kot_printed_at' => $order->kot_printed_at ?? ($order->items->where('is_bar_item', false)->count() > 0 ? now() : null),
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
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $subtotal,
            'discount_amount' => (float) $discount,
            'total' => (float) $total,
            'payment_method' => $validated['payment_method'],
            'amount_paid' => (float) $amountPaid,
            'change_amount' => (float) $change,
            'items' => $order->items->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'discount_percent' => (float) $item->discount_percent,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
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
            'items' => $order->items->map(fn($item) => [
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
