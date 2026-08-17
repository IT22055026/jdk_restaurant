<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'supplier_id',
        'product_code',
        'description',
        'cost_price',
        'price',
        'selling_price',
        'quantity',
        'low_stock_threshold',
        'is_unlimited_stock',
        'is_finished_good',
        'status',
        'barcode',
        'image',
        'supplier',
        'discount',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'quantity' => 'integer',
        'is_unlimited_stock' => 'boolean',
        'is_finished_good' => 'boolean',
        'low_stock_threshold' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplierRecord()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function wastages()
    {
        return $this->hasMany(Wastage::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('quantity_per_unit')
            ->withTimestamps();
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function hasRecipe(): bool
    {
        return $this->relationLoaded('ingredients')
            ? $this->ingredients->isNotEmpty()
            : $this->ingredients()->exists();
    }

    /**
     * How many units of this item can currently be sold.
     * - Unlimited-stock items: null (never tracked).
     * - Finished goods (e.g. bottled drinks): their own `quantity` column.
     * - Recipe-tracked items (e.g. cooked dishes): computed from ingredient stock on hand.
     */
    public function availableStock(): ?int
    {
        if ($this->is_unlimited_stock) {
            return null;
        }

        if ($this->is_finished_good) {
            return (int) $this->quantity;
        }

        $recipe = $this->relationLoaded('ingredients') ? $this->ingredients : $this->ingredients()->get();

        if ($recipe->isEmpty()) {
            return 0;
        }

        $unitsPossible = $recipe->min(function ($ingredient) {
            $perUnit = (float) $ingredient->pivot->quantity_per_unit;
            return $perUnit > 0 ? ((float) $ingredient->quantity / $perUnit) : INF;
        });

        return (int) floor($unitsPossible);
    }

    /**
     * Low-stock alerting only applies to finished goods, which carry their own
     * quantity/threshold. Recipe-tracked items are alerted on via their ingredients
     * (see the Ingredients page) instead.
     */
    public function isLowStock(): bool
    {
        if ($this->is_unlimited_stock || !$this->is_finished_good || is_null($this->low_stock_threshold)) {
            return false;
        }
        return (int) $this->quantity <= $this->low_stock_threshold;
    }
}
