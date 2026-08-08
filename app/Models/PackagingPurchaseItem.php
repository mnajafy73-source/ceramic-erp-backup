<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'packaging_id',
        'quantity',
        'total_price',
        'price_per_unit',
    ];

    public function purchase()
    {
        return $this->belongsTo(PackagingPurchase::class, 'purchase_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }
}