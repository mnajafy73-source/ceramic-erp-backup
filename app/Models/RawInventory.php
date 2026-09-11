<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function updateStock($productId, $stock)
    {
        self::updateOrCreate(
            ['product_id' => $productId],
            ['stock' => max(0, $stock)]
        );
    }
}