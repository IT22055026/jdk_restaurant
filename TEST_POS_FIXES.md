# POS Cart & KOT Fixes - Test Scenarios

## Fixes Applied

### 1. Backend: PosController.php - addItem()
**Change**: Modified to search for unprinted items only
```php
$existingItem = OrderItem::where('order_id', $order->id)
    ->where('product_id', $product->id)
    ->where('kot_printed', false)  // ← NEW
    ->first();
```

**Behavior**:
- If unprinted item exists → increment quantity
- If no unprinted item exists → create new line item

### 2. Backend: PosController.php - printKot()
**Change**: Only print and mark unprinted items
```php
$unprintedItems = $kitchenItems->filter(fn($item) => !$item->kot_printed);

if ($unprintedItems->isEmpty()) {
    return error response
}

OrderItem::whereIn('id', $unprintedItemIds)->update(['kot_printed' => true]);
```

**Behavior**:
- Only shows new items not yet sent to kitchen
- Marks them as printed so they won't print again
- Returns error if no new items

### 3. Frontend: pos.blade.php - addProductToOrder()
**Change**: Check for unprinted items when deciding to increment or create new
```javascript
const existing = currentOrder.items.find(function(i) {
    return i.product_id === productId && (!i.kot_printed);
});

if (existing) {
    existing.quantity++;
} else {
    currentOrder.items.push({
        ...
        kot_printed: false
    });
}
```

**Behavior**:
- Frontend and backend logic synchronized
- Only increment qty if item not yet printed
- Create new line if item was already printed

---

## Test Scenarios

### Scenario 1: Add Same Product Twice Before KOT Print ✅

1. Select Table 4
2. Click "Fried Rice" (add first time)
   - **Expected**: Fried Rice qty=1, Rs. 1000.00
3. Click "Fried Rice" (add second time)
   - **Expected**: Fried Rice qty=2, Rs. 2000.00 (SAME LINE, NOT NEW)
4. Print KOT
   - **Expected**: Kitchen gets "Fried Rice ×2"
   - **Result**: Both items marked kot_printed=true

### Scenario 2: Add Item, Print KOT, Add Same Item Again ✅

1. Continue from Scenario 1
2. Add Cocacola
   - **Expected**: New line "Cocacola qty=1, Rs. 800.00"
3. Print KOT
   - **Expected**: Kitchen gets "Fried Rice ×2, Cocacola ×1"
   - **Result**: All 3 items marked kot_printed=true
4. Add Fried Rice again (3rd time overall, 2nd time after KOT)
   - **Expected**: NEW LINE "Fried Rice qty=1" (separate from first Fried Rice qty=2)
   - **Why**: Old item has kot_printed=true, so frontend creates new line
5. Print KOT again
   - **Expected**: Kitchen gets ONLY "Fried Rice ×1" (the new one)
   - **NOT shown**: The original "Fried Rice ×2" (already printed)

### Scenario 3: Add Multiple New Items After KOT ✅

1. Table 4 has: Fried Rice ×2 (printed), Cocacola ×1 (printed)
2. Add Fried Rice again
   - **Expected**: New line "Fried Rice qty=1"
3. Add Cocacola again
   - **Expected**: New line "Cocacola qty=1" (NOT incrementing existing)
4. Add Cocacola 3rd time
   - **Expected**: Incrementing the NEW Cocacola line to qty=2
5. Print KOT
   - **Expected**: Kitchen gets:
     - Fried Rice ×1 (new)
     - Cocacola ×2 (new)
   - **NOT shown**: Original items already printed

---

## Database State After Test

### order_items Table

| id | order_id | product_id | product_name | quantity | kot_printed |
|----|----------|-----------|--------------|----------|-------------|
| 1  | 1        | 1         | Fried Rice   | 2        | true       |
| 2  | 1        | 2         | Cocacola     | 1        | true       |
| 3  | 1        | 1         | Fried Rice   | 1        | true       |
| 4  | 1        | 2         | Cocacola     | 2        | true       |

Notice: Multiple rows for same product because they were added at different times relative to KOT printing.

---

## Code Flow Diagram

```
addProductToOrder(productId)
    ↓
Frontend optimistic update:
    find item where product_id=X AND !kot_printed
    ├─ Found → qty++
    └─ Not found → create new line (kot_printed: false)
    ↓
Send POST /order/:id/item {product_id, quantity: 1}
    ↓
Backend addItem():
    find item where product_id=X AND !kot_printed
    ├─ Found → qty += 1, save
    └─ Not found → create new line (kot_printed: false)
    ↓
Frontend syncOrder():
    GET /order/:id (includes kot_printed in response)
    ↓
renderBill():
    Display all items with correct quantities and statuses
```

---

## Verification Checklist

- [x] Backend accepts kot_printed in getOrder() response
- [x] Backend addItem() queries for unprinted items only
- [x] Backend printKot() filters unprinted items only
- [x] Frontend addProductToOrder() checks kot_printed status
- [x] Frontend initializes new items with kot_printed: false
- [x] OrderItem model includes kot_printed in fillable & casts
- [x] Migration adds kot_printed column (boolean, default false)
