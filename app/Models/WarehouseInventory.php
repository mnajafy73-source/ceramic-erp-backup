<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInventory extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inventories';

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