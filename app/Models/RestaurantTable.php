<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $table = 'restaurant_tables';

    protected $fillable = [
        'table_number',
        'name',
        'capacity',
        'status',
        'section',
        'occupied_at',
    ];

    protected $casts = [
        'occupied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function activeOrder()
    {
        return $this->hasOne(Order::class, 'table_id')
            ->whereIn('status', ['pending', 'confirmed', 'hold'])
            ->latest();
    }
}
