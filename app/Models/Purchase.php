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

    /** How a line item was sourced — drives which relation (if any) it's linked to. */
    public const TYPE_FINISHED_GOOD = 'finished_good';
    public const TYPE_INCLUDED_ITEM = 'included_item';
    public const TYPE_OTHER = 'other';

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank' => 'Bank Transfer',
        'card' => 'Card',
        'split' => 'Split Payment',
    ];

    protected $fillable = [
        'purchase_category_id',
        'supplier_id',
        'product_id',
        'ingredient_id',
        'user_id',
        'item_name',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'purchase_date',
        'reference_no',
        'supplier_invoice_no',
        'payment_method',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }

    /** Where this line item came from: a catalog Finished Good, an Included Item, or a manual/"other" entry. */
    public function itemType(): string
    {
        if ($this->product_id) {
            return self::TYPE_FINISHED_GOOD;
        }
        if ($this->ingredient_id) {
            return self::TYPE_INCLUDED_ITEM;
        }
        return self::TYPE_OTHER;
    }

    public function itemTypeLabel(): string
    {
        return match ($this->itemType()) {
            self::TYPE_FINISHED_GOOD => 'Finished Good',
            self::TYPE_INCLUDED_ITEM => 'Included Item',
            default => 'Other',
        };
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }
}
