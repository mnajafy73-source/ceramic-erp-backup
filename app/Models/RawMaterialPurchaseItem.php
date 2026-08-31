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
        'quantity', // به گرم ذخیره می‌شود
        'total_price',
        'price_per_gram',
        'unit', // واحد انتخابی کاربر (kg یا ton) - برای نمایش
    ];

    public function purchase()
    {
        return $this->belongsTo(RawMaterialPurchase::class, 'purchase_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * نمایش مقدار به تن
     */
    public function getQuantityInTonAttribute()
    {
        return $this->quantity / 1000000;
    }

    /**
     * نمایش مقدار به کیلوگرم
     */
    public function getQuantityInKgAttribute()
    {
        return $this->quantity / 1000;
    }

    /**
     * نمایش مقدار با واحد انتخابی کاربر
     */
    public function getDisplayQuantityAttribute()
    {
        if ($this->unit === 'ton') {
            $val = $this->quantity_in_ton;
            $formatted = rtrim(rtrim(number_format($val, 3, '.', ''), '0'), '.');
            return ($formatted === '' ? '0' : $formatted) . ' تن';
        }
        $val = $this->quantity_in_kg;
        $formatted = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
        return ($formatted === '' ? '0' : $formatted) . ' کیلوگرم';
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->quantity > 0 && $item->purchase) {
                $transportCost = $item->purchase->total_transport_cost ?? 0;
                $totalItems = $item->purchase->items()->count();
                $transportShare = ($totalItems > 0) ? ($transportCost / $totalItems) : 0;

                $item->price_per_gram = ($item->total_price + $transportShare) / $item->quantity;
            }
        });
    }
}