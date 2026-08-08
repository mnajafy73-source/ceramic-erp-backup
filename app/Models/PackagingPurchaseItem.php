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

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->quantity > 0 && $item->purchase) {
                // ===== فرمول صحیح (تقسیم مساوی هزینه حمل بین آیتم‌ها) =====
                $transportCost = $item->purchase->total_transport_cost ?? 0;
                $totalItems = $item->purchase->items()->count();
                $transportShare = ($totalItems > 0) ? ($transportCost / $totalItems) : 0;
                
                // قیمت هر واحد = (قیمت کل آیتم + سهم حمل) / تعداد
                $item->price_per_unit = ($item->total_price + $transportShare) / $item->quantity;
            }
        });
    }
}