<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'date',
        'customer_name',
        'invoice_id',
        'tax_percent',
        'total_price',
        'total_with_tax',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}