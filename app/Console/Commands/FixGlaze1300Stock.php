<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Glaze1300Inventory;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;

class FixGlaze1300Stock extends Command
{
    protected $signature = 'glaze1300:fix-stock';
    protected $description = 'محاسبه موجودی ۱۳۰۰ درجه بر اساس موجودی اول دوره، خروجی کوره ۲ و بسته‌بندی‌ها';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی ۱۳۰۰ درجه...');

        $products = Product::where('status', 1)->get();

        foreach ($products as $product) {
            // موجودی اول دوره ۱۳۰۰
            $opening = OpeningInventory::where('product_id', $product->id)->value('quantity') ?? 0;

            // خروجی شاتل کوره ۲
            $glazeProduction = ShuttleFiring::where('product_id', $product->id)
                ->where('kiln_type', 'kiln_2')
                ->sum('output_quantity');

            // بسته‌بندی کوره ۲
            $packagedFromKiln2 = ShuttleFiring::where('product_id', $product->id)
                ->where('kiln_type', 'kiln_2')
                ->where('is_packaged', 1)
                ->sum('output_quantity');

            // بسته‌بندی کوره ۴
            $packagedFromKiln4 = ShuttleFiring::where('product_id', $product->id)
                ->where('kiln_type', 'kiln_4')
                ->where('is_packaged', 1)
                ->sum('output_quantity');

            $stock = $opening + $glazeProduction - $packagedFromKiln2 - $packagedFromKiln4;

            Glaze1300Inventory::updateOrCreate(
                ['product_id' => $product->id],
                ['stock' => max(0, $stock)]
            );

            $this->line("   {$product->name}: {$opening} + {$glazeProduction} - {$packagedFromKiln2} - {$packagedFromKiln4} = " . max(0, $stock));
        }

        $this->info('✅ موجودی ۱۳۰۰ درجه با موفقیت به‌روز شد.');
        return 0;
    }
}