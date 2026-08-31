<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\WaxInventory;
use Illuminate\Console\Command;

class WaxFixStock extends Command
{
    protected $signature = 'wax:fix-stock';
    protected $description = 'محاسبه و ذخیره موجودی موم برای همه محصولات';

    public function handle()
    {
        $products = Product::where('status', 1)->get();
        $count = 0;

        foreach ($products as $product) {
            $stock = ShuttleFiring::getMumStock($product->id);
            WaxInventory::updateStock($product->id, $stock);
            $count++;
        }

        $this->info("✅ موجودی موم برای {$count} محصول به‌روز شد.");
    }
}