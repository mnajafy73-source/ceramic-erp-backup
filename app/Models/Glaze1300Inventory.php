<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Glaze1300Inventory extends Model
{
    use HasFactory;

    protected $table = 'glaze_1300_inventories';

    protected $fillable = [
        'product_id',
        'stock',
        'imported_delta_sum',   // ✅ اضافه شد
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'imported_delta_sum' => 'decimal:4',   // ✅ اضافه شد
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function updateStock($productId, $stock)
    {
        self::updateOrCreate(
            ['product_id' => $productId],
            ['stock' => $stock]
        );
    }
}