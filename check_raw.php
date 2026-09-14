<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Production;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;
use App\Models\RawInventory;

$names = ['بلسن', 'ترموکوپل', 'نمونه بردار', 'دلند'];

foreach ($names as $name) {
    $p = Product::where('name', $name)->first();
    if (!$p) {
        echo "❌ $name پیدا نشد\n";
        continue;
    }

    $production    = Production::where('product_id', $p->id)->whereNotNull('press_id')->sum('quantity');
    $tonneliInput  = TonneliFiringItem::where('product_id', $p->id)->sum('input_quantity');
    $shuttleOutput = ShuttleFiring::where('product_id', $p->id)
                        ->whereIn('kiln_type', ['kiln_1','kiln_2','kiln_3','kiln_4'])
                        ->sum('output_quantity');

    $rawInv = RawInventory::where('product_id', $p->id)->first();

    $calc1 = max(0, $production - $tonneliInput);                    // مثل بلسن
    $calc2 = max(0, $production - $tonneliInput - $shuttleOutput);   // فعلی
    $calc3 = ShuttleFiring::getRawStock($p->id);                     // متد اصلی

    echo "\n========== $name (id={$p->id}) ==========\n";
    echo "نوع محصول:                   {$p->product_type}\n";
    echo "تولید (با press_id):        {$production}\n";
    echo "ورودی کوره تونلی:           {$tonneliInput}\n";
    echo "خروجی کوره شاتل:            {$shuttleOutput}\n";
    echo "───────────────────────────\n";
    echo "🟢 محاسبه مثل بلسن:          {$calc1}\n";
    echo "🔴 محاسبه فعلی (با شاتل):     {$calc2}\n";
    echo "⚙️  getRawStock():            {$calc3}\n";
    echo "───────────────────────────\n";
    echo "📦 RawInventory.stock (DB):  " . ($rawInv->stock ?? 'NULL') . "\n";
}