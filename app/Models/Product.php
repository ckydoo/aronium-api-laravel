<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'aronium_product_id',
        'company_id',
        'name',
        'code',
        'barcode',
        'description',
        'price',
        'cost',
        'category_id',
        'category_name',
        'tax_id',
        'tax_code',
        'tax_percent',
        'unit',
        'is_active',
        'track_inventory',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    /**
     * Get the company that owns the product.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the stock record for the product.
     */
    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * Get all stock movements for this product.
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get sale items for this product.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'aronium_product_id');
    }

    /**
     * Get purchase items for this product.
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'aronium_product_id', 'aronium_product_id');
    }
}