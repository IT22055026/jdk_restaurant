<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    /**
     * Deduct finished-good product stock for order items being newly confirmed to the
     * kitchen (token print). Mirrors IngredientStockService but operates on products.quantity
     * for items that are their own stock unit (e.g. bottled drinks), not ingredient-tracked.
     *
     * $itemDeltas is an array of ['item' => OrderItem, 'delta' => int] where delta is the
     * newly-confirmed quantity for that item in this token print batch (not the item's total quantity).
     *
     * Validates every product's stock across the whole batch before writing anything,
     * so a shortfall on one item never leaves another item's stock half-deducted.
     *
     * @throws InsufficientStockException
     */
    public function deductForToken(array $itemDeltas, ?int $userId): array
    {
        $required = [];

        foreach ($itemDeltas as $entry) {
            $item = $entry['item'];
            $delta = $entry['delta'];
            $product = $item->product;

            if (!$product || $product->is_unlimited_stock || !$product->is_finished_good || $delta <= 0) {
                continue;
            }

            $required[$product->id] = ($required[$product->id] ?? 0) + $delta;
        }

        if (empty($required)) {
            return [];
        }

        $lockedProducts = Product::whereIn('id', array_keys($required))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $shortfalls = [];
        foreach ($required as $productId => $qtyNeeded) {
            $product = $lockedProducts->get($productId);
            $available = $product ? (int) $product->quantity : 0;
            if ($available < $qtyNeeded) {
                $name = $product ? $product->name : "product #{$productId}";
                $shortfalls[] = "\"{$name}\" needs {$qtyNeeded}, only {$available} available";
            }
        }

        if (!empty($shortfalls)) {
            throw new InsufficientStockException('Not enough stock to print token: ' . implode('; ', $shortfalls));
        }

        $applied = [];
        foreach ($itemDeltas as $entry) {
            $item = $entry['item'];
            $delta = $entry['delta'];
            $product = $item->product;

            if (!$product || $product->is_unlimited_stock || !$product->is_finished_good || $delta <= 0) {
                continue;
            }

            $lockedProducts[$product->id]->decrement('quantity', $delta);

            StockMovement::create([
                'product_id' => $product->id,
                'change_type' => 'decrease',
                'quantity' => $delta,
                'reason' => 'Token printed',
                'source' => 'token',
                'reference_type' => OrderItem::class,
                'reference_id' => $item->id,
                'user_id' => $userId,
            ]);

            $applied[$product->id] = ($applied[$product->id] ?? 0) + $delta;
        }

        return array_map(function ($id, $qty) {
            return ['product_id' => $id, 'quantity' => $qty];
        }, array_keys($applied), $applied);
    }
}
