<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit_id',
        'weight',
        'formula_id',
        'per_box',
        'layers_per_box',
        'carton_packaging_id',
        'layer_packaging_id',
        'tonneli_feed_rate',
        'cavities',
        'per_pack',
        'per_pallet',
        'status',
        'in_production',
        'firing_process',
    ];

    // ========== ارتباط با نام‌های مستعار ==========
    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }

    // ========== سایر روابط ==========
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }

    public function cartonPackaging()
    {
        return $this->belongsTo(Packaging::class, 'carton_packaging_id');
    }

    public function layerPackaging()
    {
        return $this->belongsTo(Packaging::class, 'layer_packaging_id');
    }
}