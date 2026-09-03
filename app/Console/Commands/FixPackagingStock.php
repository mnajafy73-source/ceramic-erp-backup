<?php

namespace App\Console\Commands;

use App\Models\Packaging;
use App\Models\PackagingPurchaseItem;
use App\Models\Product;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPackagingStock extends Command
{
    protected $signature = 'packaging:fix-stock';
    protected $description = 'محاسبه و بازنشانی موجودی کارتن و لایه بر اساس خرید و مصرف (فقط تونلی و شاتل)';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی کارتن و لایه...');

        // ۱. ابتدا موجودی همه کارتن‌ها و لایه‌ها را صفر می‌کنیم
        Packaging::query()->update(['stock' => 0]);

        // ۲. محاسبه مجموع خرید هر کارتن/لایه
        $purchases = PackagingPurchaseItem::select('packaging_id')
            ->selectRaw('SUM(quantity) as total_purchased')
            ->groupBy('packaging_id')
            ->get();

        foreach ($purchases as $purchase) {
            $packaging = Packaging::find($purchase->packaging_id);
            if ($packaging) {
                $packaging->stock = $purchase->total_purchased;
                $packaging->save();
            }
        }

        // ۳. محاسبه مجموع مصرف کارتن و لایه از تونلی و شاتل (فقط بسته‌بندی‌شده‌ها)
        $consumptions = [];

        // الف) تونلی
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

        // ب) شاتل (همه کوره‌ها)
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

        // ۴. کسر مصرف از موجودی
        foreach ($consumptions as $packagingId => $consumed) {
            $packaging = Packaging::find($packagingId);
            if ($packaging) {
                $packaging->stock -= $consumed;
                $packaging->save();
                $this->line("   {$packaging->name}: خرید - مصرف = {$packaging->stock} عدد");
            }
        }

        $this->info('✅ موجودی کارتن و لایه با موفقیت به‌روز شد.');
        return 0;
    }
}