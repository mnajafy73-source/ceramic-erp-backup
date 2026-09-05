<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\TonneliFiringItem;
use App\Models\SaleProduct;
use App\Models\InformalSaleProduct;
use App\Models\WarehouseInventory;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;

class FixWarehouseStock extends Command
{
    protected $signature = 'warehouse:fix-stock';
    protected $description = 'محاسبه موجودی انبار بر اساس موجودی اول دوره، بسته‌بندی‌ها و فروش‌ها';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی انبار...');

        $products = Product::where('status', 1)->get();

        foreach ($products as $product) {
            // موجودی اول دوره انبار
            $opening = OpeningInventory::where('product_id', $product->id)->value('quantity') ?? 0;

            // بسته‌بندی تونلی
            $packagedTonneli = TonneliFiringItem::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->sum('output_quantity');

            // بسته‌بندی شاتل (کوره‌های ۱،۲،۴)
            $packagedShuttle = ShuttleFiring::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_4'])
                ->sum('output_quantity');

            // فروش رسمی
            $sales = SaleProduct::where('product_id', $product->id)->sum('quantity');

            // فروش غیررسمی
            $informalSales = InformalSaleProduct::where('product_id', $product->id)->sum('quantity');

            $stock = $opening + $packagedTonneli + $packagedShuttle - $sales - $informalSales;

            WarehouseInventory::updateOrCreate(
                ['product_id' => $product->id],
                ['stock' => max(0, $stock)]
            );

            $this->line("   {$product->name}: {$opening} + {$packagedTonneli} + {$packagedShuttle} - {$sales} - {$informalSales} = " . max(0, $stock));
        }

        $this->info('✅ موجودی انبار با موفقیت به‌روز شد.');
        return 0;
    }
}