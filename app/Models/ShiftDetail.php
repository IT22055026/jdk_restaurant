<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftDetail extends Model
{
    protected $table = 'shift_details';

    protected $fillable = [
        'shift_id',
        'opening_balance',
        'closing_balance',
        'expected_total',
        'actual_total',
        'variance',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'variance' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
