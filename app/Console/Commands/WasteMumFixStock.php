<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\WasteMumRecord;
use App\Models\WasteMumInventory;
use Illuminate\Console\Command;

class WasteMumFixStock extends Command
{
    protected $signature = 'wastemum:fix-stock';
    protected $description = 'محاسبه و ذخیره ضایعات موم برای همه محصولات';

    public function handle()
    {
        $products = Product::where('status', 1)->get();
        $count = 0;

        foreach ($products as $product) {
            $total = WasteMumRecord::where('product_name', $product->name)->sum('amount');
            WasteMumInventory::updateStock($product->id, $total);
            $count++;
        }

        $this->info("✅ ضایعات موم برای {$count} محصول به‌روز شد.");
    }
}