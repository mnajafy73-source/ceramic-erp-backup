<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit_id',
        'initial_stock',
        'firing_process',
        'kiln_type',
        'tonneli_feed_rate',
        'cavities',
        'per_box',
        'per_pack',
        'per_pallet',
        'box_type',
        'layers_per_box',
        'status',
        'in_production',
        'description',
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

    public function delete()
    {
        if ($this->logs()->exists()) {
            throw new \Exception('این کالا دارای تاریخچه است و نمی‌توان آن را حذف کرد. لطفاً آن را غیرفعال کنید.');
        }
        return parent::delete();
    }

    protected static function booted()
    {
        // تاریخچه‌ها
        static::created(function ($product) {
            $product->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'create',
                'changes' => json_encode($product->toArray()),
            ]);
        });

        static::updated(function ($product) {
            $dirty = $product->getDirty();
            if (count($dirty) === 1 && isset($dirty['updated_at'])) {
                return;
            }
            $product->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'changes' => json_encode($dirty),
            ]);
        });

        static::deleted(function ($product) {
            $product->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'delete',
                'changes' => null,
            ]);
        });

        // قبل از حذف واقعی، تولیدات را null کن
        static::deleting(function ($product) {
            \App\Models\Production::where('product_id', $product->id)->update(['product_id' => null]);
        });
    }
}