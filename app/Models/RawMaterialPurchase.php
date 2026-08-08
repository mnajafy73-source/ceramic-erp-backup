<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_date',
        'supplier',
        'total_transport_cost',
        'description',
    ];

    public function items()
    {
        return $this->hasMany(RawMaterialPurchaseItem::class, 'purchase_id');
    }
}