<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'is_active',
        'flavour_qty',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'flavour_qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'offer_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Fixed finished-good / menu products bundled directly into this offer.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'offer_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Customer choice groups (e.g. 2 Drinks from Category Drinks, 1 Dessert from Category Desserts).
     */
    public function choiceGroups()
    {
        return $this->hasMany(OfferChoiceGroup::class)->orderBy('sort_order');
    }

    /**
     * The finished-good products a customer can pick between when this offer is
     * added at POS (e.g. every Mojito flavour). Empty means the offer needs no
     * flavour choice — see offer_flavours migration.
     */
    public function flavours()
    {
        return $this->belongsToMany(Product::class, 'offer_flavours')
            ->withTimestamps();
    }
}
