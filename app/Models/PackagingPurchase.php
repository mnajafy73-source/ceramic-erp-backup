<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_date',
        'supplier',
        'total_transport_cost',
    ];

    public function items()
    {
        return $this->hasMany(PackagingPurchaseItem::class, 'purchase_id');
    }
}