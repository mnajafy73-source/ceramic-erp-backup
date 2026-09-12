<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'stock',
        'sort_order',
        'baseline_consumed',   // ✅ جدید
    ];

    protected $casts = [
        'stock'              => 'integer',
        'sort_order'         => 'integer',
        'baseline_consumed'  => 'integer',
    ];

    public function purchaseItems()
    {
        return $this->hasMany(PackagingPurchaseItem::class);
    }
}