<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShoulderRecord;
use App\Models\ShoulderInventory;
use Illuminate\Console\Command;

class ShoulderFixStock extends Command
{
    protected $signature = 'shoulder:fix-stock';
    protected $description = 'محاسبه و ذخیره موجودی شانه شده برای همه محصولات';

    public function handle()
    {
        $products = Product::where('status', 1)->get();
        $count = 0;

        foreach ($products as $product) {
            $total = ShoulderRecord::where('product_name', $product->name)->sum('total');
            ShoulderInventory::updateStock($product->id, $total);
            $count++;
        }

        $this->info("✅ موجودی شانه شده برای {$count} محصول به‌روز شد.");
    }
}