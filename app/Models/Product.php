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
        'parent_product_id',
    ];

    // ========== ارتباط با لاگ‌های محصول ==========
    public function logs()
    {
        return $this->hasMany(ProductLog::class);
    }

    // ========== ارتباط با والد (محصول خام) ==========
    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    // ========== ارتباط با فرزندان (محصولات فرآوری‌شده) ==========
    public function children()
    {
        return $this->hasMany(Product::class, 'parent_product_id');
    }

    // ========== ارتباط با نام‌های مستعار ==========
    public function aliases()
    {
        return $this->hasMany(ProductAlias::class);
    }

    // ========== ارتباط با موجودی موم ==========
    public function waxInventory()
    {
        return $this->hasOne(WaxInventory::class);
    }

    // ========== ارتباط با موجودی ۱۳۰۰ درجه ==========
    public function glaze1300Inventory()
    {
        return $this->hasOne(Glaze1300Inventory::class);
    }

    // ========== ارتباط با موجودی انبار ==========
    public function warehouseInventory()
    {
        return $this->hasOne(WarehouseInventory::class);
    }

    // ========== ارتباط با موجودی شانه شده ==========
    public function shoulderInventory()
    {
        return $this->hasOne(ShoulderInventory::class);
    }

    // ========== ارتباط با ضایعات موم ==========
    public function wasteMumInventory()
    {
        return $this->hasOne(WasteMumInventory::class);
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