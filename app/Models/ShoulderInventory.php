<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoulderInventory extends Model
{
    use HasFactory;

    protected $table = 'shoulder_inventories';

    protected $fillable = [
        'product_id',
        'stock',
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