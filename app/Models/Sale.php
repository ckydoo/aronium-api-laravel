<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_id',
        'document_number',
        'company_id',
        'date_created',
        'total',
        'tax',
        'discount',
        'customer_id',
        'user_id',
        'status',
        'fiscal_signature',
        'qr_code',
        'fiscal_invoice_number',
        'fiscalized_at',
        'tax_details',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'fiscalized_at' => 'datetime',
        'tax_details' => 'array',
        'total' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /**
     * Get the company associated with the sale
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the items for the sale
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Scope to get fiscalized sales
     */
    public function scopeFiscalized($query)
    {
        return $query->where('status', 'fiscalized')
                    ->whereNotNull('fiscal_signature');
    }

    /**
     * Scope to get pending sales
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get error sales
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope to get sales by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('date_created', [$from, $to]);
    }

    /**
     * Check if sale is fiscalized
     */
    public function isFiscalized()
    {
        return $this->status === 'fiscalized' && !empty($this->fiscal_signature);
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Get formatted tax
     */
    public function getFormattedTaxAttribute()
    {
        return '$' . number_format($this->tax, 2);
    }
}