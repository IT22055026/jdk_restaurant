<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wastage extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'reason',
        'notes',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
