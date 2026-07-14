<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'quantity',
        'low_stock_threshold',
        'supplier_id',
        'cost_per_unit',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'low_stock_threshold' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplierRecord()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(IngredientStockMovement::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity_per_unit')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        if (is_null($this->low_stock_threshold)) {
            return false;
        }
        return (float) $this->quantity <= (float) $this->low_stock_threshold;
    }
}
