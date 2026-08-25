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
     * Deduct finished-good or standalone product stock for order items being newly confirmed to the
     * kitchen (token print). Mirrors IngredientStockService but operates on products.quantity
     * for items that are their own stock unit (e.g. bottled drinks) or don't have recipe ingredients.
     *
     * $itemDeltas is an array of ['item' => OrderItem, 'delta' => int] where delta is the
     * newly-confirmed quantity for that item in this token print batch (not the item's total quantity).
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

            if (!$product || $product->is_unlimited_stock || $delta <= 0) {
                continue;
            }

            // Deduct from product table if it's explicitly a finished good OR if it has no recipe ingredients
            if ($product->is_finished_good || !$product->hasRecipe()) {
                $required[$product->id] = ($required[$product->id] ?? 0) + $delta;
            }
        }

        if (empty($required)) {
            return [];
        }

        $lockedProducts = Product::whereIn('id', array_keys($required))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Allow negative stock deduction: shortfalls do not block token print/payment
        // $lockedProducts will decrement directly into negative stock if needed.

        $applied = [];
        foreach ($itemDeltas as $entry) {
            $item = $entry['item'];
            $delta = $entry['delta'];
            $product = $item->product;

            if (!$product || $product->is_unlimited_stock || $delta <= 0) {
                continue;
            }

            if (!($product->is_finished_good || !$product->hasRecipe())) {
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
