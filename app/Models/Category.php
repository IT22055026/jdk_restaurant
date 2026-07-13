<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'color',
        'icon',
        'sort_order',
        'status',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
