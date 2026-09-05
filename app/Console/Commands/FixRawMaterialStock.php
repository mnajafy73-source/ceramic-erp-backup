<?php

namespace App\Console\Commands;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchaseItem;
use App\Models\Production;
use App\Models\Product;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRawMaterialStock extends Command
{
    protected $signature = 'raw-material:fix-stock';
    protected $description = 'محاسبه موجودی مواد اولیه بر اساس موجودی اول دوره، خریدها و تولیدات';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی مواد اولیه...');

        DB::beginTransaction();

        try {
            // ۱. دریافت موجودی اول دوره برای هر ماده
            $openingStocks = [];
            $openingItems = OpeningInventory::with('product')->get();
            foreach ($openingItems as $item) {
                // اگر محصول مربوط به مواد اولیه است
                $material = RawMaterial::where('name', $item->product->name)->first();
                if ($material) {
                    $openingStocks[$material->id] = $item->quantity;
                }
            }

            // ۲. محاسبه مجموع خرید هر ماده
            $purchases = RawMaterialPurchaseItem::with('rawMaterial')
                ->get()
                ->groupBy('raw_material_id');

            // ۳. محاسبه مصرف هر ماده از تولیدات
            $productions = Production::with(['product.formula.items.rawMaterial'])
                ->where('stage', 'production')
                ->get();

            $materialConsumption = [];

            foreach ($productions as $production) {
                $product = $production->product;
                if (!$product || !$product->weight || !$product->formula_id) {
                    continue;
                }

                $totalMaterialGram = $production->quantity * $product->weight;

                foreach ($product->formula->items as $item) {
                    $consumedGram = ($totalMaterialGram * $item->percentage) / 100;
                    $materialId = $item->raw_material_id;

                    if (!isset($materialConsumption[$materialId])) {
                        $materialConsumption[$materialId] = 0;
                    }
                    $materialConsumption[$materialId] += $consumedGram;
                }
            }

            // ۴. محاسبه موجودی نهایی = موجودی اول دوره + خرید - مصرف
            foreach (RawMaterial::all() as $material) {
                $opening = $openingStocks[$material->id] ?? 0;
                $purchased = $purchases->has($material->id) ? $purchases[$material->id]->sum('quantity') : 0;
                $consumed = $materialConsumption[$material->id] ?? 0;

                $stock = $opening + $purchased - $consumed;
                $material->stock = max(0, $stock);
                $material->save();

                $this->line("   {$material->name}: {$opening} + {$purchased} - {$consumed} = {$stock} گرم");
            }

            DB::commit();

            $this->info('✅ موجودی مواد اولیه با موفقیت محاسبه شد.');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ خطا: ' . $e->getMessage());
            return 1;
        }
    }
}