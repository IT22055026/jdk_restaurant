<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\ShiftTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $tables = RestaurantTable::all()->load('activeOrder.items');
        $categories = Category::where('status', 'active')->orderBy('sort_order')->get();
        $products = Product::where('status', 'active')->get();
        $modules = $this->currentUser()->role->modules()->get();

        $activeShift = Shift::where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        return view('modules.pos', [
            'tables' => $tables,
            'categories' => $categories,
            'products' => $products,
            'modules' => $modules,
            'activeShift' => $activeShift,
        ]);
    }


    public function getTables()
    {
        $tables = RestaurantTable::with('activeOrder.items')->orderBy('table_number')->get()->map(function ($table) {
            $activeOrder = $table->activeOrder;
            return [
                'id' => $table->id,
                'table_number' => $table->table_number,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'status' => $table->status,
                'section' => $table->section,
                'occupied_at' => $table->occupied_at,
                'has_order' => $activeOrder ? true : false,
                'order_id' => $activeOrder?->id,
                'order_items_count' => $activeOrder?->items->count() ?? 0,
            ];
        });

        return response()->json($tables);
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

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) ($product->selling_price ?? $product->price),
                'cost_price' => (float) ($product->cost_price ?? 0),
                'category_id' => $product->category_id,
                'barcode' => $product->barcode,
                'is_unlimited_stock' => $product->is_unlimited_stock,
                'quantity' => $product->quantity,
                'image' => $product->image,
            ];
        });

        return response()->json($products);
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'order_type' => 'required|in:dine_in,takeaway,delivery,vip_room',
            'waiter_name' => 'nullable|string',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-' . Str::random(8),
            'table_id' => $validated['table_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'user_id' => $this->currentUser()->id,
            'order_type' => $validated['order_type'],
            'waiter_name' => $validated['waiter_name'] ?? $this->currentUser()->name,
        ]);

        if (!empty($validated['table_id'])) {
            RestaurantTable::find($validated['table_id'])->update([
                'status' => 'occupied',
                'occupied_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function getOrder(Order $order)
    {
        $order->load('items.product', 'table', 'customer');

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
            'table_id' => $order->table_id,
            'table_number' => $order->table?->table_number,
            'order_type' => $order->order_type,
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

        if (!$product->is_unlimited_stock) {
            $product->decrement('quantity', $validated['quantity']);
        }

        $this->updateOrderTotals($order);

        $product->refresh();
        $lowStockAlert = null;
        if ($product->isLowStock()) {
            $lowStockAlert = "\"{$product->name}\" is low on stock — only {$product->quantity} unit(s) left.";
        }

        return response()->json([
            'success' => true,
            'item_id' => $item->id,
            'item_kot_printed' => (bool) $item->kot_printed,
            'message' => $product->name . ' added to order',
            'low_stock_alert' => $lowStockAlert,
        ]);
    }

    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        Product::where('id', $item->product_id)
            ->where('is_unlimited_stock', false)
            ->increment('quantity', $item->quantity);

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

        $diff = $validated['quantity'] - $item->quantity;
        if ($diff > 0) {
            Product::where('id', $item->product_id)
                ->where('is_unlimited_stock', false)
                ->decrement('quantity', $diff);
        } elseif ($diff < 0) {
            Product::where('id', $item->product_id)
                ->where('is_unlimited_stock', false)
                ->increment('quantity', abs($diff));
        }

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
            'table_number' => $order->table?->table_number,
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

    public function printKot(Order $order)
    {
        $order->load('items');

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
                    'message' => 'KOT already printed, history record updated.',
                    'order_number' => $order->order_number,
                    'items' => []
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No new items to print. All items already sent to kitchen.',
                'order_number' => $order->order_number,
            ], 422);
        }

        // Prepare items to print and update printed_qty
        $itemsToPrint = [];
        foreach ($printableItems as $item) {
            $qtyToPrint = $item->quantity - $item->printed_qty;

            $itemsToPrint[] = [
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
            'items' => $itemsToPrint,
        ]);
    }

    public function getHeldOrders()
    {
        $orders = Order::where('status', 'hold')->with('table', 'items')->latest()->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->table_number,
            'items_count' => $order->items->count(),
            'total' => (float) $order->total,
        ]));
    }

    public function getQrOrderNotifications()
    {
        $orders = Order::where('source', 'qr')
            ->whereIn('status', ['pending', 'confirmed', 'hold'])
            ->with('table', 'items')
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'unseen_count' => $orders->whereNull('seen_at')->count(),
            'orders' => $orders->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'table_number' => $order->table?->table_number,
                'table_name' => $order->table?->name,
                'customer_name' => $order->customer_name,
                'items_count' => $order->items->count(),
                'items_summary' => $order->items->map(fn($i) => $i->quantity . '× ' . $i->product_name)->implode(', '),
                'total' => (float) $order->total,
                'seen' => ! is_null($order->seen_at),
                'created_at' => $order->created_at->toIso8601String(),
                'created_at_human' => $order->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markQrOrderSeen(Order $order)
    {
        if ($order->source === 'qr' && is_null($order->seen_at)) {
            $order->update(['seen_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllQrOrdersSeen()
    {
        Order::where('source', 'qr')->whereNull('seen_at')->update(['seen_at' => now()]);

        return response()->json(['success' => true]);
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
        $order->load('items', 'table');
        $order->update(['waiter_bill_printed_at' => now()]);

        $subtotal = $order->items->sum('subtotal');
        $total = $subtotal;

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->table_number,
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

    public function closeTable(Order $order)
    {
        $order->load('items');
        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)
                ->where('is_unlimited_stock', false)
                ->increment('quantity', $item->quantity);
        }

        $order->update(['status' => 'cancelled']);
        if ($order->table_id) {
            RestaurantTable::find($order->table_id)->update([
                'status' => 'available',
                'occupied_at' => null,
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Table closed']);
    }

    public function payOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,bank_transfer,mixed',
            'amount_paid' => 'required|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $order->load('items', 'table');
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

        // Only update table status if this order is for dine-in (has a table_id)
        if ($order->table_id) {
            $table = RestaurantTable::find($order->table_id);
            if ($table) {
                $table->update([
                    'status' => 'available',
                    'occupied_at' => null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->table_number,
            'table_name' => $order->table?->name,
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

    public function getTableOrders(RestaurantTable $table)
    {
        $orders = $table->orders()->with('items')->latest()->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'items_count' => $order->items->count(),
            'subtotal' => (float) $order->subtotal,
            'total' => (float) $order->total,
            'created_at' => $order->created_at,
        ]));
    }

    public function orderHistory()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.order-history', ['modules' => $modules]);
    }

    public function getOrderHistory(Request $request)
    {
        $query = Order::with('table', 'items')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%");
        }

        if ($request->has('order_type') && $request->input('order_type') !== 'all') {
            $query->where('order_type', $request->input('order_type'));
        }

        $orders = $query->get();

        return response()->json($orders->map(fn($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'table_number' => $order->table?->table_number,
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

    public function kotHistory()
    {
        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.kot-history', ['modules' => $modules]);
    }

    public function getKotHistory(Request $request)
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
            'customer_name' => $order->customer_name ?? 'N/A',
            'items_count' => $order->items->count(),
            'kot_printed_at' => $order->kot_printed_at?->format('M d, Y H:i'),
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
        $order->load('items', 'table');

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->table_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'tax_amount' => (float) $order->tax_amount,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'amount_paid' => (float) $order->amount_paid,
            'change_amount' => (float) $order->change_amount,
            'order_type' => $order->order_type,
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

    public function reprintKot(Order $order)
    {
        $order->load('items');
        $kitchenItems = $order->items->where('is_bar_item', false)->values();

        return response()->json([
            'success' => true,
            'order_number' => $order->order_number,
            'table_number' => $order->table?->table_number ?? '—',
            'customer_name' => $order->customer_name,
            'order_type' => $order->order_type,
            'date_time' => now()->format('M d, Y H:i'),
            'is_reprint' => true,
            'items' => $kitchenItems->map(fn($item) => [
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'kitchen_notes' => $item->kitchen_notes,
            ]),
        ]);
    }

    public function generateTableQrCode(RestaurantTable $table)
    {
        try {
            $qrUrl = route('table.order.menu', ['tableId' => $table->id], true);

            $renderer = new \BaconQrCode\Renderer\Image\Svg();
            $renderer->setHeight(200);
            $renderer->setWidth(200);

            $qrCode = \BaconQrCode\Encoder\Encoder::encode(
                $qrUrl,
                \BaconQrCode\Common\ErrorCorrectionLevel::H,
                \BaconQrCode\Encoding\Encoding::UTF_8
            );

            $image = $renderer->render($qrCode);

            return response($image, 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'inline; filename="table-' . $table->table_number . '-qr.svg"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate QR code: ' . $e->getMessage()], 500);
        }
    }

    public function scanTableQr(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required|string',
        ]);

        try {
            $data = json_decode($validated['qr_data'], true);

            if (!$data || $data['type'] !== 'table' || !isset($data['table_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format',
                ], 422);
            }

            $table = RestaurantTable::find($data['table_id']);
            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found',
                ], 404);
            }

            $activeOrder = $table->activeOrder;

            return response()->json([
                'success' => true,
                'table' => [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'name' => $table->name,
                    'status' => $table->status,
                    'section' => $table->section,
                ],
                'order' => $activeOrder ? [
                    'id' => $activeOrder->id,
                    'order_number' => $activeOrder->order_number,
                    'status' => $activeOrder->status,
                    'has_active_order' => true,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAllTableQrCodes()
    {
        try {
            $tables = RestaurantTable::all();
            $qrCodes = [];

            foreach ($tables as $table) {
                $qrUrl = route('table.order.menu', ['tableId' => $table->id], true);

                try {
                    $renderer = new \BaconQrCode\Renderer\Image\Svg();
                    $renderer->setHeight(200);
                    $renderer->setWidth(200);

                    $qrCode = \BaconQrCode\Encoder\Encoder::encode(
                        $qrUrl,
                        \BaconQrCode\Common\ErrorCorrectionLevel::H,
                        \BaconQrCode\Encoding\Encoding::UTF_8
                    );

                    $image = $renderer->render($qrCode);

                    $qrCodes[] = [
                        'table_id' => $table->id,
                        'table_number' => $table->table_number,
                        'table_name' => $table->name,
                        'qr_svg' => $image->__toString(),
                    ];
                } catch (\Exception $e) {
                    \Log::error('QR code generation failed for table ' . $table->id . ': ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'qr_codes' => $qrCodes,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate QR codes: ' . $e->getMessage()], 500);
        }
    }

    public function tableQrCodesPrint()
    {
        $tables = RestaurantTable::orderBy('table_number')->get();
        $qrCodes = [];

        foreach ($tables as $table) {
            $qrUrl = route('table.order.menu', ['tableId' => $table->id], true);

            try {
                $renderer = new \BaconQrCode\Renderer\Image\Svg();
                $renderer->setHeight(250);
                $renderer->setWidth(250);

                $qrCode = \BaconQrCode\Encoder\Encoder::encode(
                    $qrUrl,
                    \BaconQrCode\Common\ErrorCorrectionLevel::H,
                    \BaconQrCode\Encoding\Encoding::UTF_8
                );

                $image = $renderer->render($qrCode);

                $qrCodes[] = [
                    'table_id' => $table->id,
                    'table_number' => $table->table_number,
                    'table_name' => $table->name,
                    'section' => $table->section,
                    'capacity' => $table->capacity,
                    'qr_url' => $qrUrl,
                    'qr_svg' => $image->__toString(),
                ];
            } catch (\Exception $e) {
                \Log::error('QR code generation failed for table ' . $table->id . ': ' . $e->getMessage());
            }
        }

        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.table-qr-codes', ['qrCodes' => $qrCodes, 'modules' => $modules]);
    }

    public function tableQrCodesSettings()
    {
        $tables = RestaurantTable::orderBy('table_number')->get();
        $qrCodes = [];

        foreach ($tables as $table) {
            $qrUrl = route('table.order.menu', ['tableId' => $table->id], true);

            // Generate QR code using free API
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrUrl);

            $qrCodes[] = [
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'table_name' => $table->name,
                'section' => $table->section,
                'capacity' => $table->capacity,
                'qr_url' => $qrUrl,
                'qr_image_url' => $qrImageUrl,
            ];
        }

        $modules = $this->currentUser()->role->modules()->get();
        return view('modules.table-qr-settings', ['qrCodes' => $qrCodes, 'modules' => $modules]);
    }

    public function downloadTableQrCode($tableId)
    {
        $table = RestaurantTable::findOrFail($tableId);
        $qrUrl = route('table.order.menu', ['tableId' => $table->id], true);

        try {
            // Use free QR API to generate PNG
            $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&data=' . urlencode($qrUrl);
            $qrImageData = file_get_contents($apiUrl);

            return response($qrImageData)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="table-' . $table->table_number . '-qr.png"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate QR code: ' . $e->getMessage()], 500);
        }
    }

    public function tableQrMenu($tableId)
    {
        $table = RestaurantTable::findOrFail($tableId);
        $categories = Category::where('status', 'active')->orderBy('sort_order')->get();
        $products = Product::where('status', 'active')->get();

        return view('qr-menu.table-order', [
            'table' => $table,
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    public function submitTableOrder(Request $request, $tableId)
    {
        $table = RestaurantTable::findOrFail($tableId);

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.kitchen_notes' => 'nullable|string',
        ]);

        try {
            // Check if table already has an active order
            $existingOrder = Order::where('table_id', $table->id)
                ->whereIn('status', ['pending', 'confirmed', 'hold'])
                ->latest()
                ->first();

            if ($existingOrder) {
                // Add items to existing order
                $order = $existingOrder;
                $isExistingOrder = true;
            } else {
                // Create new order
                $order = Order::create([
                    'order_number' => 'ORD-' . Str::random(8),
                    'table_id' => $table->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'user_id' => auth()->check() ? auth()->id() : 1,
                    'order_type' => 'dine_in',
                    'source' => 'qr',
                    'status' => 'pending',
                ]);
                $isExistingOrder = false;
            }

            // Add items to order
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $itemSubtotal = $product->price * $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $itemSubtotal,
                    'kitchen_notes' => $item['kitchen_notes'] ?? null,
                ]);
            }

            // Update order totals
            $allItems = $order->items()->get();
            $totalSubtotal = $allItems->sum('subtotal');
            $order->update([
                'subtotal' => $totalSubtotal,
                'total' => $totalSubtotal,
            ]);

            // New items were just added via the QR menu — surface this order
            // in the notification bell again, even if it was already seen.
            if ($isExistingOrder && $order->source === 'qr' && $order->seen_at !== null) {
                $order->update(['seen_at' => null]);
            }

            // Update customer info if provided and new order
            if (!$isExistingOrder && $validated['customer_name']) {
                $order->update([
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                ]);
            }

            // Ensure table is marked as occupied
            if ($table->status !== 'occupied') {
                $table->update([
                    'status' => 'occupied',
                    'occupied_at' => now(),
                ]);
            }

            $message = $isExistingOrder
                ? 'Items added to existing order! Your items have been sent to the kitchen.'
                : 'Order placed successfully! Your order has been sent to the kitchen.';

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'table_number' => $table->table_number,
                'is_new_order' => !$isExistingOrder,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error placing order: ' . $e->getMessage(),
            ], 500);
        }
    }
}
