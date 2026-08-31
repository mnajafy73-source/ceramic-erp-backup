<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Glaze1300Inventory;
use Illuminate\Console\Command;

class Glaze1300FixStock extends Command
{
    protected $signature = 'glaze1300:fix-stock';
    protected $description = 'محاسبه و ذخیره موجودی ۱۳۰۰ درجه برای همه محصولات';

    public function handle()
    {
        $products = Product::where('status', 1)->get();
        $count = 0;

        foreach ($products as $product) {
            $stock = ShuttleFiring::getGlaze1300Stock($product->id);
            Glaze1300Inventory::updateStock($product->id, $stock);
            $count++;
        }

        $this->info("✅ موجودی ۱۳۰۰ درجه برای {$count} محصول به‌روز شد.");
    }
}