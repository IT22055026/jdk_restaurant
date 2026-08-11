<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientStockMovement extends Model
{
    protected $fillable = [
        'ingredient_id',
        'change_type',
        'quantity',
        'reason',
        'source',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
        'name',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
