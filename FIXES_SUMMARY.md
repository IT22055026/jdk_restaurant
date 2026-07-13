# POS Billing Issues - Fixes Summary

## Problems Reported

1. **Duplicate Items in Cart**: When clicking the same product twice, it creates separate line items instead of increasing the quantity
2. **KOT Printing Issue**: Once KOT is printed, the system doesn't print only new items added afterward
3. **Missing KOT for New Items**: When adding items after KOT print, they should create new KOT entries for the kitchen

---

## Root Causes Identified

### Issue 1: Frontend & Backend Mismatch on Duplicate Detection

**Frontend** (pos.blade.php:990):
```javascript
// OLD - finds ANY item with same product_id
const existing = currentOrder.items.find(i => i.product_id === productId);
```

**Backend** (PosController.php:174):
```php
// OLD - finds ANY item with same product_id
$existingItem = OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->first();
```

**Problem**: After KOT is printed, the item has `kot_printed=true`. The query still finds it and checks the old logic of "if printed, create new; if not printed, increment". But the frontend doesn't know about `kot_printed` status, so it does its own optimistic update (incrementing) while the backend creates a new item. They diverge!

### Issue 2: KOT Printing Logic Was Incomplete

**Old printKot()** in PosController (before fix):
```php
$kitchenItems = $order->items->where('is_bar_item', false);
// Returns ALL kitchen items regardless of print status
```

**Problem**: It would show and print items that were already sent to the kitchen, causing duplicate KOT printouts.

---

## Solutions Implemented

### Fix 1: Backend - addItem() Method

**File**: `app/Http/Controllers/PosController.php` (lines 162-211)

```php
// Find any existing item for this product in the order
$existingItem = OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)  // ← NEW: Only find unprinted items
    ->first();

if ($existingItem) {
    // If item exists and hasn't been printed yet, increase the quantity
    $existingItem->quantity += $validated['quantity'];
    $existingItem->subtotal = $existingItem->unit_price * $existingItem->quantity;
    $existingItem->save();
    $item = $existingItem;
} else {
    // Either new item or existing item that was already printed
    // If item was already printed, create a new line item for the additional quantity
    // Otherwise create the first line item
    $item = OrderItem::create([...]);
}
```

**Result**:
- ✅ Unprinted items: quantity increases on same line
- ✅ Printed items: new line item created (kitchen notified)
- ✅ New items: always create new line initially

### Fix 2: Backend - printKot() Method

**File**: `app/Http/Controllers/PosController.php` (lines 331-362)

```php
$order->load('items');
$kitchenItems = $order->items->where('is_bar_item', false)->values();

// Get items that have NOT been printed yet (kot_printed = false)
$unprintedItems = $kitchenItems->filter(fn($item) => !$item->kot_printed);

if ($unprintedItems->isEmpty()) {
    return response()->json([
        'success' => false,
        'message' => 'No new items to print. All items already sent to kitchen.',
        'order_number' => $order->order_number,
    ], 422);
}

// Get the IDs of unprinted items
$unprintedItemIds = $unprintedItems->pluck('id')->toArray();

// Mark these items as printed using query builder
OrderItem::whereIn('id', $unprintedItemIds)->update(['kot_printed' => true]);

return response()->json([
    'success' => true,
    'order_number' => $order->order_number,
    'items' => $unprintedItems->map(fn($item) => [
        'product_name' => $item->product_name,
        'quantity' => $item->quantity,
        'kitchen_notes' => $item->kitchen_notes,
    ]),
]);
```

**Result**:
- ✅ Only prints items not yet sent to kitchen
- ✅ Marks them as `kot_printed=true` immediately
- ✅ Returns error if no new items exist
- ✅ Prevents duplicate KOT printouts

### Fix 3: Frontend - addProductToOrder() Function

**File**: `resources/views/modules/pos.blade.php` (lines 990-998)

```javascript
// Optimistic update - only increase qty if item exists and NOT printed to kitchen
const existing = currentOrder.items.find(function(i) {
    return i.product_id === productId && (!i.kot_printed);
});

if (existing) {
    existing.quantity++;
    existing.subtotal = existing.unit_price * existing.quantity;
} else {
    currentOrder.items.push({
        id: null, product_id: productId, product_name: productName,
        unit_price: price, quantity: 1, subtotal: price, 
        kitchen_notes: null, 
        kot_printed: false  // ← NEW: Track print status
    });
}
```

**Result**:
- ✅ Frontend now aware of `kot_printed` status
- ✅ Only increments qty if item hasn't been printed
- ✅ Creates new line for already-printed items
- ✅ Frontend and backend logic synchronized

### Fix 4: Model & Data Return

**File**: `app/Models/OrderItem.php`
```php
protected $fillable = [
    ...
    'kot_printed',  // ← Added
];

protected $casts = [
    ...
    'kot_printed' => 'boolean',  // ← Added
];
```

**File**: `app/Http/Controllers/PosController.php` - getOrder() method
```php
return response()->json([
    ...
    'items' => $order->items->map(function ($item) {
        return [
            ...
            'kot_printed' => (bool) $item->kot_printed,  // ← Return to frontend
        ];
    }),
]);
```

---

## Test Results

### Test Case 1: Add Same Product Twice Before KOT

**Steps**:
1. Select Table 4
2. Click "Fried Rice" → Qty: 1
3. Click "Fried Rice" → Qty: 2 (SAME LINE)
4. Print KOT → Kitchen gets "Fried Rice ×2"

**Expected**: Same line, qty increases to 2
**Status**: ✅ PASS

### Test Case 2: Add Item, Print KOT, Add Same Item Again

**Steps**:
1. Table 4 has Fried Rice ×2, Cocacola ×1
2. Print KOT → All marked `kot_printed=true`
3. Add Fried Rice → NEW LINE (separate from first)
4. Print KOT → Kitchen gets only new "Fried Rice ×1"

**Expected**: New line created, only new item prints
**Status**: ✅ PASS

### Test Case 3: Multiple Additions After KOT

**Steps**:
1. Add Cocacola (2nd time) → NEW LINE
2. Add Cocacola (3rd time) → Increments new line to qty=2
3. Print KOT → Kitchen gets "Cocacola ×2" (new items only)

**Expected**: Separate lines for different print cycles, increments within cycle
**Status**: ✅ PASS

---

## Files Changed

| File | Changes | Lines |
|------|---------|-------|
| `app/Http/Controllers/PosController.php` | addItem() & printKot() logic | 162-211, 331-362 |
| `app/Models/OrderItem.php` | Added kot_printed to fillable & casts | 9-30 |
| `resources/views/modules/pos.blade.php` | addProductToOrder() respects kot_printed | 990-998 |

---

## Database

**Migration**: `database/migrations/2026_06_08_231846_add_kot_printed_to_order_items_table.php`

Adds column:
```sql
ALTER TABLE order_items ADD COLUMN kot_printed BOOLEAN DEFAULT FALSE AFTER is_bar_item;
```

---

## How It Works Now

```
Timeline: Table 4 orders Rice (1000), Coke (800)

T1: User adds Rice
    Frontend: creates new item {product_id: 1, quantity: 1, kot_printed: false}
    Backend: no existing unprinted rice, creates new item
    
T2: User adds Rice again
    Frontend: finds unprinted rice, qty++
    Backend: finds unprinted rice, qty++
    Result: Single line "Rice ×2"
    
T3: User clicks Print KOT
    Backend: finds unprinted items, marks as printed
    Kitchen receives: Rice ×2, Coke ×1
    Database: both items now kot_printed=true
    
T4: User adds Rice AGAIN (customer wants more)
    Frontend: searches for product_id=1 AND !kot_printed
    No match (existing rice has kot_printed=true)
    Creates NEW item: {product_id: 1, quantity: 1, kot_printed: false}
    Backend: same logic, creates new line
    Result: TWO separate rice lines in cart
    
T5: User clicks Print KOT again
    Backend: finds ONLY the new rice (kot_printed=false)
    Kitchen receives: Rice ×1 (the new one)
    Old Rice ×2 is NOT reprinted
    Database: new item now marked kot_printed=true
```

---

## Commits

1. `1ca2c3c` - fix: Resolve POS cart quantity and KOT printing issues
2. `fbadded` - fix: Frontend now respects kot_printed status when adding items to cart

---

## Testing Instructions

1. **Start server**: `php artisan serve`
2. **Navigate to**: http://127.0.0.1:8000/pos
3. **Login** with your user account
4. **Select Table 4**
5. **Add Fried Rice** twice → Should show qty: 2 (same line)
6. **Add Cocacola** once
7. **Print KOT** → Should print both items
8. **Add Fried Rice** again → Should create NEW line (separate entry)
9. **Print KOT** → Should print only the new rice, not the original qty:2
10. **Verify in bill**: Multiple line items for same product

---

## Benefits

✅ **Correct Cart Behavior**: Same product before KOT = quantity increase
✅ **Smart Kitchen Orders**: After KOT = separate line for kitchen tracking  
✅ **No Duplicate Printing**: KOT marks items as printed, prevents reprinting
✅ **Accurate Billing**: All items show in final bill with correct quantities
✅ **Kitchen Workflow**: Always sees exactly what needs to be prepared

