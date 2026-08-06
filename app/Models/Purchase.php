<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    public const UNITS = [
        'kg' => 'Kilograms (kg)',
        'ltr' => 'Litres (ltr)',
        'pcs' => 'Number (pcs)',
    ];

    protected $fillable = [
        'purchase_category_id',
        'supplier_id',
        'user_id',
        'item_name',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PurchaseCategory::class, 'purchase_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }
}
