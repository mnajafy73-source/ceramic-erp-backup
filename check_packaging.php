<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Packaging;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;

$product = Product::where('name', '2.3ک')->first();
if (!$product) { echo "محصول پیدا نشد\n"; exit; }

echo "══════════════════════════════════════════════════\n";
echo "  محصول: {$product->name} (id={$product->id})\n";
echo "══════════════════════════════════════════════════\n";
echo "per_box:          " . ($product->per_box ?? 'NULL') . "\n";
echo "layers_per_box:   " . ($product->layers_per_box ?? 'NULL') . "\n";
echo "carton_packaging_id: " . ($product->carton_packaging_id ?? 'NULL') . "\n";
echo "layer_packaging_id:  " . ($product->layer_packaging_id ?? 'NULL') . "\n";

$carton = Packaging::find($product->carton_packaging_id);
$layer  = Packaging::find($product->layer_packaging_id);

echo "\n── کارتن تعریف‌شده ──\n";
if ($carton) {
    echo "نام: {$carton->name}\n";
    echo "نوع: {$carton->type}\n";
    echo "stock فعلی: {$carton->stock}\n";
    echo "baseline_consumed: " . ($carton->baseline_consumed ?? 'NULL') . "\n";
} else {
    echo "⚠️ کارتن پیدا نشد!\n";
}

echo "\n── لایه تعریف‌شده ──\n";
if ($layer) {
    echo "نام: {$layer->name}\n";
    echo "نوع: {$layer->type}\n";
    echo "stock فعلی: {$layer->stock}\n";
    echo "baseline_consumed: " . ($layer->baseline_consumed ?? 'NULL') . "\n";
} else {
    echo "⚠️ لایه پیدا نشد!\n";
}

// محاسبه مصرف واقعی
$tonneliQty = TonneliFiringItem::where('product_id', $product->id)
    ->where('is_packaged', 1)
    ->where('output_quantity', '>', 0)
    ->sum('output_quantity');

$shuttleQty = ShuttleFiring::where('product_id', $product->id)
    ->where('is_packaged', 1)
    ->where('output_quantity', '>', 0)
    ->sum('output_quantity');

$totalQty = $tonneliQty + $shuttleQty;

echo "\n── بسته‌بندی‌های انجام‌شده ──\n";
echo "از کوره تونلی: {$tonneliQty}\n";
echo "از کوره شاتل:  {$shuttleQty}\n";
echo "جمع:            {$totalQty}\n";

if ($product->per_box > 0) {
    $cartonNeed = ceil($totalQty / $product->per_box);
    echo "\n── محاسبه مصرف ──\n";
    echo "کارتن لازم:  {$cartonNeed} (÷ {$product->per_box})\n";
    if ($product->layers_per_box > 0) {
        $layerNeed = $cartonNeed * $product->layers_per_box;
        echo "لایه لازم:    {$layerNeed} ({$cartonNeed} × {$product->layers_per_box})\n";
    }
}

// همه TonneliFiringItem های این محصول
echo "\n── جزئیات TonneliFiringItem های این محصول ──\n";
$items = TonneliFiringItem::where('product_id', $product->id)
    ->where('is_packaged', 1)
    ->get();

if ($items->isEmpty()) {
    echo "هیچ آیتم بسته‌بندی‌شده‌ای از کوره تونلی نداره\n";
} else {
    foreach ($items as $it) {
        echo "  - id={$it->id}, output={$it->output_quantity}, is_packaged={$it->is_packaged}\n";
    }
}

echo "\n── جزئیات ShuttleFiring های این محصول ──\n";
$shuttleItems = ShuttleFiring::where('product_id', $product->id)
    ->where('is_packaged', 1)
    ->get();

if ($shuttleItems->isEmpty()) {
    echo "هیچ آیتم بسته‌بندی‌شده‌ای از کوره شاتل نداره\n";
} else {
    foreach ($shuttleItems as $it) {
        echo "  - id={$it->id}, kiln={$it->kiln_type}, output={$it->output_quantity}\n";
    }
}