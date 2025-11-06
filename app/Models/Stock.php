<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'company_id',
        'quantity',
        'available_quantity',
        'reserved_quantity',
        'reorder_level',
        'reorder_quantity',
        'location',
        'last_restocked_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'last_restocked_at' => 'datetime',
    ];

    /**
     * Get the product that owns the stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the company that owns the stock.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if product needs reordering.
     */
    public function needsReorder(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }
        
        return $this->available_quantity <= $this->reorder_level;
    }

    /**
     * Update stock quantity after a movement.
     */
    public function adjustQuantity(float $amount, string $type = 'adjustment'): void
    {
        $this->quantity += $amount;
        $this->available_quantity += $amount;
        
        if ($amount > 0 && in_array($type, ['purchase', 'adjustment'])) {
            $this->last_restocked_at = now();
        }
        
        $this->save();
    }
}