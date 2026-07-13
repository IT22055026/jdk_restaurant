<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTransaction extends Model
{
    protected $fillable = [
        'shift_id',
        'order_id',
        'transaction_type',
        'amount',
        'payment_method',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
