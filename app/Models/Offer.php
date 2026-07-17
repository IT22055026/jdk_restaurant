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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'offer_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
