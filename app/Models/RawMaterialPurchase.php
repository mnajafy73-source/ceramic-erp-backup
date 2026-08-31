<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUndo;

class RawMaterialPurchase extends Model
{
    use HasFactory, HasUndo;

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