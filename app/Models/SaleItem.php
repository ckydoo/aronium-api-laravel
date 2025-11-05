<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'discount',
        'tax',
        'total',
        'tax_id',
        'tax_code',
        'tax_percent',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_percent' => 'decimal:2',
    ];

    /**
     * Get the sale that owns the item
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Calculate line total
     */
    public function calculateTotal()
    {
        return ($this->quantity * $this->price) - $this->discount + $this->tax;
    }
}