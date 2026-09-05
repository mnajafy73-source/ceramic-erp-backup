<?php

namespace App\Console\Commands;

use App\Models\Packaging;
use App\Models\PackagingPurchaseItem;
use App\Models\Product;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPackagingStock extends Command
{
    protected $signature = 'packaging:fix-stock';
    protected $description = 'محاسبه موجودی کارتن و لایه بر اساس موجودی اول دوره، خریدها و مصرف';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی کارتن و لایه...');

        // ۱. موجودی اول دوره کارتن‌ها
        $openingStocks = [];
        $openingItems = OpeningInventory::with('product')->get();
        foreach ($openingItems as $item) {
            // اگر محصول دارای کارتن است
            $product = $item->product;
            if ($product && $product->carton_packaging_id) {
                $openingStocks[$product->carton_packaging_id] = $item->quantity;
            }
            if ($product && $product->layer_packaging_id) {
                $openingStocks[$product->layer_packaging_id] = $item->quantity;
            }
        }

        // ۲. مجموع خرید هر کارتن/لایه
        $purchases = PackagingPurchaseItem::select('packaging_id')
            ->selectRaw('SUM(quantity) as total_purchased')
            ->groupBy('packaging_id')
            ->get();

        // ۳. محاسبه مصرف کارتن و لایه از تونلی و شاتل
        $consumptions = [];

        // تونلی
        $tonneliItems = TonneliFiringItem::with('product')
            ->where('is_packaged', 1)
            ->where('output_quantity', '>', 0)
            ->get();

        foreach ($tonneliItems as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;

            if ($product->carton_packaging_id && $product->per_box > 0) {
                $cartonCount = ceil($qty / $product->per_box);
                $consumptions[$product->carton_packaging_id] = ($consumptions[$product->carton_packaging_id] ?? 0) + $cartonCount;
            }
            if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $cartonCount = ceil($qty / $product->per_box);
                $layerCount = $cartonCount * $product->layers_per_box;
                $consumptions[$product->layer_packaging_id] = ($consumptions[$product->layer_packaging_id] ?? 0) + $layerCount;
            }
        }

        // شاتل
        $shuttleItems = ShuttleFiring::with('product')
            ->where('is_packaged', 1)
            ->where('output_quantity', '>', 0)
            ->get();

        foreach ($shuttleItems as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;

            if ($product->carton_packaging_id && $product->per_box > 0) {
                $cartonCount = ceil($qty / $product->per_box);
                $consumptions[$product->carton_packaging_id] = ($consumptions[$product->carton_packaging_id] ?? 0) + $cartonCount;
            }
            if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $cartonCount = ceil($qty / $product->per_box);
                $layerCount = $cartonCount * $product->layers_per_box;
                $consumptions[$product->layer_packaging_id] = ($consumptions[$product->layer_packaging_id] ?? 0) + $layerCount;
            }
        }

        // ۴. محاسبه موجودی نهایی = موجودی اول دوره + خرید - مصرف
        foreach (Packaging::all() as $packaging) {
            $opening = $openingStocks[$packaging->id] ?? 0;
            $purchased = $purchases->firstWhere('packaging_id', $packaging->id)->total_purchased ?? 0;
            $consumed = $consumptions[$packaging->id] ?? 0;

            $stock = $opening + $purchased - $consumed;
            $packaging->stock = max(0, $stock);
            $packaging->save();

            $this->line("   {$packaging->name}: {$opening} + {$purchased} - {$consumed} = " . max(0, $stock));
        }

        $this->info('✅ موجودی کارتن و لایه با موفقیت به‌روز شد.');
        return 0;
    }
}