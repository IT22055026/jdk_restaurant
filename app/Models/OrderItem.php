<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'offer_id',
        'ingredient_id',
        'is_offer_component',
        'offer_component_qty',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
        'discount_percent',
        'kitchen_notes',
        'is_bar_item',
        'kot_printed',
        'printed_qty',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_bar_item' => 'boolean',
        'is_offer_component' => 'boolean',
        'offer_component_qty' => 'decimal:3',
        'kot_printed' => 'boolean',
        'printed_qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
