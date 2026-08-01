<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformalSaleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'informal_sale_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    public function sale()
    {
        return $this->belongsTo(InformalSale::class, 'informal_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}