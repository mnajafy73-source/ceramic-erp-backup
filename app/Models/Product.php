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
        'product_type', // ✅ اضافه شد
        'parent_product_id',
    ];

    // ========== ارتباطات ==========
    public function logs()
    {
        return $this->hasMany(ProductLog::class);
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function children()
    {
        return $this->hasMany(Product::class, 'parent_product_id');
    }

    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }

    public function waxInventory()
    {
        return $this->hasOne(WaxInventory::class);
    }

    public function glaze1300Inventory()
    {
        return $this->hasOne(Glaze1300Inventory::class);
    }

    public function warehouseInventory()
    {
        return $this->hasOne(WarehouseInventory::class);
    }

    public function shoulderInventory()
    {
        return $this->hasOne(ShoulderInventory::class);
    }

    public function wasteMumInventory()
    {
        return $this->hasOne(WasteMumInventory::class);
    }

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

    // ========== متدهای کمکی ==========
    public function isInjection(): bool
    {
        return $this->product_type === 'injection';
    }

    public function isNormal(): bool
    {
        return $this->product_type === 'normal' || is_null($this->product_type);
    }
}