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

        $shortfalls = [];
        foreach ($required as $ingredientId => $qtyNeeded) {
            $ingredient = $lockedIngredients->get($ingredientId);
            $available = $ingredient ? (float) $ingredient->quantity : 0;
            if ($available < $qtyNeeded) {
                $name = $ingredient ? $ingredient->name : "ingredient #{$ingredientId}";
                $unit = $ingredient ? $ingredient->unit : '';
                $shortfalls[] = sprintf(
                    '"%s" needs %s %s, only %s %s available',
                    $name,
                    rtrim(rtrim(number_format($qtyNeeded, 3), '0'), '.'),
                    $unit,
                    rtrim(rtrim(number_format($available, 3), '0'), '.'),
                    $unit
                );
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
