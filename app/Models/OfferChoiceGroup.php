<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferChoiceGroup extends Model
{
    protected $fillable = [
        'offer_id',
        'name',
        'category_id',
        'choice_qty',
        'sort_order',
    ];

    protected $casts = [
        'choice_qty' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'offer_choice_group_products', 'offer_choice_group_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * Get all active products eligible for this choice group.
     * If specific products are selected for this group, returns those products.
     * Otherwise if a category is selected, returns all active products in that category.
     */
    public function getEligibleProducts()
    {
        if ($this->products()->count() > 0) {
            return $this->products()->where('status', 'active')->get();
        }

        if ($this->category_id) {
            return Product::where('category_id', $this->category_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        return collect();
    }
}
