<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'status',
        'notes',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
