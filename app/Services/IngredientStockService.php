<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Ingredient;
use App\Models\IngredientStockMovement;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class IngredientStockService
{
    /**
     * Deduct ingredient stock for order items being newly confirmed to the kitchen (token print).
     *
     * $itemDeltas is an array of ['item' => OrderItem, 'delta' => int] where delta is the
     * newly-confirmed quantity for that item in this token print batch (not the item's total quantity).
     *
     * Validates every ingredient requirement across the whole batch before writing anything,
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

            // Included items bundled directly into an offer (no product in between —
            // see PosController::addOffer()) deduct at the offer's per-unit rate
            // (offer_component_qty), skipping the recipe lookup below.
            if ($item->ingredient_id && $delta > 0) {
                $qty = (float) ($item->offer_component_qty ?? 1) * $delta;
                $required[$item->ingredient_id] = ($required[$item->ingredient_id] ?? 0) + $qty;
                continue;
            }

            $product = $item->product;

            if (!$product || $product->is_unlimited_stock || $product->is_finished_good || $delta <= 0) {
                continue;
            }

            $recipe = $product->ingredients()->get();
            if ($recipe->isEmpty()) {
                throw new InsufficientStockException(
                    "No recipe defined for \"{$item->product_name}\". Add its ingredients under Products → Recipe before printing the token."
                );
            }

            foreach ($recipe as $ingredient) {
                $qty = (float) $ingredient->pivot->quantity_per_unit * $delta;
                $required[$ingredient->id] = ($required[$ingredient->id] ?? 0) + $qty;
            }
        }

        if (empty($required)) {
            return [];
        }

        $lockedIngredients = Ingredient::whereIn('id', array_keys($required))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Allow negative stock deduction: ingredient shortfalls do not block token print/payment
        // $lockedIngredients will decrement directly into negative stock if needed.

        $applied = [];
        foreach ($itemDeltas as $entry) {
            $item = $entry['item'];
            $delta = $entry['delta'];

            if ($item->ingredient_id && $delta > 0) {
                $qty = (float) ($item->offer_component_qty ?? 1) * $delta;
                $lockedIngredients[$item->ingredient_id]->decrement('quantity', $qty);

                IngredientStockMovement::create([
                    'ingredient_id' => $item->ingredient_id,
                    'change_type' => 'decrease',
                    'quantity' => $qty,
                    'reason' => 'Offer component (token printed)',
                    'source' => 'token',
                    'reference_type' => OrderItem::class,
                    'reference_id' => $item->id,
                    'user_id' => $userId,
                ]);

                $applied[$item->ingredient_id] = ($applied[$item->ingredient_id] ?? 0) + $qty;
                continue;
            }

            $product = $item->product;

            if (!$product || $product->is_unlimited_stock || $product->is_finished_good || $delta <= 0) {
                continue;
            }

            foreach ($product->ingredients()->get() as $ingredient) {
                $qty = (float) $ingredient->pivot->quantity_per_unit * $delta;
                if ($qty <= 0) {
                    continue;
                }

                $lockedIngredients[$ingredient->id]->decrement('quantity', $qty);

                IngredientStockMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'change_type' => 'decrease',
                    'quantity' => $qty,
                    'reason' => 'Token printed',
                    'source' => 'token',
                    'reference_type' => OrderItem::class,
                    'reference_id' => $item->id,
                    'user_id' => $userId,
                ]);

                $applied[$ingredient->id] = ($applied[$ingredient->id] ?? 0) + $qty;
            }
        }

        return array_map(function ($id, $qty) {
            return ['ingredient_id' => $id, 'quantity' => $qty];
        }, array_keys($applied), $applied);
    }
}
