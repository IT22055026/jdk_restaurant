<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'token_date',
        'token_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'user_id',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'payment_method',
        'amount_paid',
        'change_amount',
        'notes',
        'waiter_name',
        'live_bill_enabled',
        'kot_printed_at',
        'bot_printed_at',
        'waiter_bill_printed_at',
        'printed_at',
        'discard_reason',
        'discarded_by',
        'discarded_at',
    ];

    protected $casts = [
        'token_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'live_bill_enabled' => 'boolean',
        'kot_printed_at' => 'datetime',
        'bot_printed_at' => 'datetime',
        'waiter_bill_printed_at' => 'datetime',
        'printed_at' => 'datetime',
        'discarded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discardedBy()
    {
        return $this->belongsTo(User::class, 'discarded_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
