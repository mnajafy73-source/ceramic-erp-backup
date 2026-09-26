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
        'baseline_consumed',
        'imported_delta_sum',   // ✅ اضافه شد
    ];

    protected $casts = [
        'stock'              => 'decimal:4',    // ✅ تغییر: integer → decimal
        'sort_order'         => 'integer',
        'baseline_consumed'  => 'integer',
        'imported_delta_sum' => 'decimal:4',   // ✅ اضافه شد
    ];

    public function purchaseItems()
    {
        return $this->hasMany(PackagingPurchaseItem::class);
    }
}