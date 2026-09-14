<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Packaging;
use App\Models\Product;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;

echo "═══════════════════════════════════════════════════\n";
echo "  مصرف پیش‌بینی‌شده‌ی همه‌ی کارتن‌ها و لایه‌ها\n";
echo "═══════════════════════════════════════════════════\n\n";

foreach (Packaging::orderBy('type')->orderBy('name')->get() as $pkg) {
    $totalConsumed = 0;
    $usedByProducts = [];

    // همه محصولات فعال
    foreach (Product::where('status', 1)->get() as $product) {
        $productConsumed = 0;

        // اگر این پکیجینگ به عنوان کارتن استفاده شده
        if ($product->carton_packaging_id == $pkg->id && $product->per_box > 0) {
            $tonneliQty = TonneliFiringItem::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->where('output_quantity', '>', 0)
                ->sum('output_quantity');
            $shuttleQty = ShuttleFiring::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->where('output_quantity', '>', 0)
                ->sum('output_quantity');
            $totalQty = $tonneliQty + $shuttleQty;

            if ($totalQty > 0) {
                $count = ceil($totalQty / $product->per_box);
                $productConsumed += $count;
            }
        }

        // اگر این پکیجینگ به عنوان لایه استفاده شده
        if ($product->layer_packaging_id == $pkg->id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $tonneliQty = TonneliFiringItem::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->where('output_quantity', '>', 0)
                ->sum('output_quantity');
            $shuttleQty = ShuttleFiring::where('product_id', $product->id)
                ->where('is_packaged', 1)
                ->where('output_quantity', '>', 0)
                ->sum('output_quantity');
            $totalQty = $tonneliQty + $shuttleQty;

            if ($totalQty > 0) {
                $count = ceil($totalQty / $product->per_box) * $product->layers_per_box;
                $productConsumed += $count;
            }
        }

        if ($productConsumed > 0) {
            $usedByProducts[] = "{$product->name}: {$productConsumed}";
            $totalConsumed += $productConsumed;
        }
    }

    echo "── [{$pkg->type}] {$pkg->name} (id={$pkg->id}) ──\n";
    echo "   stock فعلی:          {$pkg->stock}\n";
    echo "   baseline_consumed:   " . ($pkg->baseline_consumed ?? 'NULL') . "\n";
    echo "   مصرف پیش‌بینی‌شده:     {$totalConsumed}\n";

    if ($pkg->baseline_consumed !== null) {
        $delta = $totalConsumed - $pkg->baseline_consumed;
        $predictedStock = max(0, $pkg->stock - $delta);
        echo "   → delta:             {$delta}\n";
        echo "   → stock پیش‌بینی:     {$predictedStock}\n";
    } else {
        echo "   ⚠️ baseline_consumed خالیه (مقدار مصرف اولیه محاسبه نمیشه)\n";
    }

    if (!empty($usedByProducts)) {
        echo "   استفاده‌کنندگان:\n";
        foreach ($usedByProducts as $u) {
            echo "     - {$u}\n";
        }
    } else {
        echo "   ⚠️ هیچ محصولی از این استفاده نمی‌کند\n";
    }
    echo "\n";
}