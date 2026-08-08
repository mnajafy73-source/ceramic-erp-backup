<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'quantity',
        'total_price',
        'price_per_gram',
    ];

    public function purchase()
    {
        return $this->belongsTo(RawMaterialPurchase::class, 'purchase_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->quantity > 0 && $item->purchase) {
                // ===== فرمول صحیح (تقسیم مساوی هزینه حمل بین آیتم‌ها) =====
                $transportCost = $item->purchase->total_transport_cost ?? 0;
                $totalItems = $item->purchase->items()->count();
                $transportShare = ($totalItems > 0) ? ($transportCost / $totalItems) : 0;
                
                // قیمت هر گرم = (قیمت کل آیتم + سهم حمل) / (مقدار به کیلوگرم * 1000)
                $item->price_per_gram = ($item->total_price + $transportShare) / ($item->quantity * 1000);
            }
        });
    }
}