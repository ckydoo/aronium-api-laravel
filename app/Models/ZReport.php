<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'aronium_report_id',
        'company_id',
        'report_date',
        'report_number',
        'device_id',
        'device_name',
        'total_transactions',
        'total_items_sold',
        'gross_sales',
        'discounts',
        'returns',
        'net_sales',
        'total_tax',
        'payment_breakdown',
        'tax_breakdown',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'opened_at',
        'closed_at',
        'opened_by',
        'closed_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'gross_sales' => 'decimal:2',
        'discounts' => 'decimal:2',
        'returns' => 'decimal:2',
        'net_sales' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'payment_breakdown' => 'array',
        'tax_breakdown' => 'array',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the company that owns the report.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if there's a cash discrepancy.
     */
    public function hasCashDiscrepancy(): bool
    {
        if ($this->cash_difference === null) {
            return false;
        }
        
        return abs($this->cash_difference) > 0.01;
    }

    /**
     * Scope to get reports by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get reports for a specific device.
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'company_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_id',
        'reference_type',
        'notes',
        'user_id',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    /**
     * Get the product associated with this movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the company that owns the movement.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the reference document (sale, purchase, etc).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get movements by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope to get movements in date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }
}