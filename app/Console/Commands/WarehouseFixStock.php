<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\WarehouseInventory;
use Illuminate\Console\Command;

class WarehouseFixStock extends Command
{
    protected $signature = 'warehouse:fix-stock';
    protected $description = 'محاسبه و ذخیره موجودی انبار برای همه محصولات';

    public function handle()
    {
        $products = Product::where('status', 1)->get();
        $count = 0;

        foreach ($products as $product) {
            $stock = ShuttleFiring::getWarehouseStock($product->id);
            WarehouseInventory::updateStock($product->id, $stock);
            $count++;
        }

        $this->info("✅ موجودی انبار برای {$count} محصول به‌روز شد.");
    }
}