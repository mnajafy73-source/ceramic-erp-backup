<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUndo;

class Product extends Model
{
    use HasFactory, HasUndo;

    protected $fillable = [
        'code', 'name', 'unit_id',
        'tonneli_feed_rate', 'cavities', 'per_box', 'per_pack',
        'per_pallet', 'box_type', 'layers_per_box', 'status', 'in_production',
        'firing_process', // این فیلد در دیتابیس وجود دارد و باید fillable باشد
        // فیلدهای جدید
        'weight', 'formula_id',
    ];

    protected $casts = [
        'status' => 'boolean',
        'in_production' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function logs()
    {
        return $this->hasMany(ProductLog::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    // رابطه جدید: هر محصول به یک فرمول مربوط است
    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }

    public function delete()
    {
        if ($this->logs()->exists()) {
            throw new \Exception('این کالا دارای تاریخچه است و نمی‌توان آن را حذف کرد.');
        }
        return parent::delete();
    }
}