<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaxInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * به‌روزرسانی یا ایجاد موجودی موم برای یک محصول
     */
    public static function updateStock($productId, $stock)
    {
        self::updateOrCreate(
            ['product_id' => $productId],
            ['stock' => $stock]
        );
    }
}