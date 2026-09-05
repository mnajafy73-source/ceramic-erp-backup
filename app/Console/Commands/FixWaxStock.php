<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\WaxInventory;
use App\Models\ShoulderInventory;
use App\Models\WasteMumInventory;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWaxStock extends Command
{
    protected $signature = 'wax:fix-stock';
    protected $description = 'محاسبه موجودی موم بر اساس موجودی اول دوره، خروجی شاتل موم، شانه شده و ضایعات موم';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی موم...');

        $products = Product::where('status', 1)->get();

        foreach ($products as $product) {
            // موجودی اول دوره موم
            $opening = OpeningInventory::where('product_id', $product->id)->value('quantity') ?? 0;

            // خروجی شاتل موم
            $mumProduction = ShuttleFiring::where('product_id', $product->id)
                ->where('kiln_type', 'kiln_3')
                ->where('firing_subtype', 'mum')
                ->sum('output_quantity');

            // موجودی شانه شده
            $shoulderStock = ShoulderInventory::where('product_id', $product->id)->value('stock') ?? 0;

            // ضایعات موم
            $wasteMum = WasteMumInventory::where('product_id', $product->id)->value('stock') ?? 0;

            $stock = $opening + $mumProduction - $shoulderStock - $wasteMum;

            WaxInventory::updateOrCreate(
                ['product_id' => $product->id],
                ['stock' => max(0, $stock)]
            );

            $this->line("   {$product->name}: {$opening} + {$mumProduction} - {$shoulderStock} - {$wasteMum} = " . max(0, $stock));
        }

        $this->info('✅ موجودی موم با موفقیت به‌روز شد.');
        return 0;
    }
}