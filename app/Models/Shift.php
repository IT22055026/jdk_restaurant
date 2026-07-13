<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'opening_balance',
        'closing_balance',
        'expected_total',
        'actual_total',
        'variance',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasOne(ShiftDetail::class);
    }

    public function transactions()
    {
        return $this->hasMany(ShiftTransaction::class);
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}
