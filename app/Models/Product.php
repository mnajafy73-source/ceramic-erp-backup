<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasUndo;

class Product extends Model
{
    use HasFactory, HasUndo;

    protected $fillable = [
        'code', 'name', 'unit_id', 'initial_stock', 'firing_process',
        'kiln_type', 'tonneli_feed_rate', 'cavities', 'per_box', 'per_pack',
        'per_pallet', 'box_type', 'layers_per_box', 'status', 'in_production', 'description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'in_production' => 'boolean',
    ];

    public function unit() { return $this->belongsTo(Unit::class); }
    public function logs() { return $this->hasMany(ProductLog::class); }

    public function delete()
    {
        if ($this->logs()->exists()) {
            throw new \Exception('این کالا دارای تاریخچه است و نمی‌توان آن را حذف کرد.');
        }
        return parent::delete();
    }
}