<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'aronium_document_id',
        'document_number',
        'company_id',
        'date_created',
        'supplier_id',
        'supplier_name',
        'subtotal',
        'tax',
        'discount',
        'total',
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the company that owns the purchase.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the items for the purchase.
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Scope to get received purchases.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope to get pending purchases.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'aronium_product_id',
        'product_name',
        'product_code',
        'quantity',
        'cost',
        'discount',
        'tax',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the purchase that owns the item.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the product associated with this item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'aronium_product_id', 'aronium_product_id');
    }
}