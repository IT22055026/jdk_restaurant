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
        'is_unlimited_stock' => 'boolean',
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

    public function isLowStock(): bool
    {
        if ($this->is_unlimited_stock || is_null($this->low_stock_threshold)) {
            return false;
        }
        return $this->quantity <= $this->low_stock_threshold;
    }
}
