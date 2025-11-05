<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'tax_id',
        'vat_number',
        'address',
        'phone',
        'email',
    ];

    /**
     * Get the sales for the company
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get total revenue for the company
     */
    public function getTotalRevenueAttribute()
    {
        return $this->sales()->sum('total');
    }

    /**
     * Get fiscalized sales count
     */
    public function getFiscalizedSalesCountAttribute()
    {
        return $this->sales()->fiscalized()->count();
    }

    /**
     * Get pending sales count
     */
    public function getPendingSalesCountAttribute()
    {
        return $this->sales()->pending()->count();
    }
}


