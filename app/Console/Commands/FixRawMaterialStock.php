<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchaseItem;
use App\Models\Production;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FixRawMaterialStock extends Command
{
    protected $signature = 'raw-material:fix-stock';
    protected $description = 'محاسبه و بازنشانی موجودی مواد اولیه بر اساس خریدها و تولیدات با فرمول صحیح';

    public function handle()
    {
        $this->info('🔄 شروع بازنشانی موجودی مواد اولیه...');

        DB::beginTransaction();

        try {
            // ۱. تنظیم موجودی همه مواد به صفر
            RawMaterial::query()->update(['stock' => 0]);
            $this->info('✅ موجودی مواد اولیه به صفر تنظیم شد.');

            // ۲. محاسبه مجموع خرید هر ماده (بر حسب گرم)
            $purchases = RawMaterialPurchaseItem::with('rawMaterial')
                ->get()
                ->groupBy('raw_material_id');

            $this->info('📊 محاسبه خریدها...');

            foreach ($purchases as $materialId => $items) {
                $totalPurchased = $items->sum('quantity'); // به گرم
                $rawMaterial = RawMaterial::find($materialId);
                if ($rawMaterial) {
                    $rawMaterial->stock += $totalPurchased;
                    $rawMaterial->save();
                    $this->line("   🔹 {$rawMaterial->name}: +{$totalPurchased} گرم (خرید)");
                }
            }

            // ۳. محاسبه مصرف هر ماده از تولیدات (بر حسب گرم)
            $this->info('📊 محاسبه مصرف تولیدات...');

            $productions = Production::with(['product.formula.items.rawMaterial'])
                ->where('stage', 'production')
                ->get();

            $materialConsumption = [];

            foreach ($productions as $production) {
                $product = $production->product;
                if (!$product || !$product->weight || !$product->formula_id) {
                    continue;
                }

                // محاسبه مصرف به گرم (مستقیم)
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

            // ۴. کسر مصرف از موجودی
            foreach ($materialConsumption as $materialId => $consumedGram) {
                $rawMaterial = RawMaterial::find($materialId);
                if ($rawMaterial) {
                    $oldStock = $rawMaterial->stock;
                    $rawMaterial->stock -= $consumedGram;
                    $rawMaterial->save();
                    $this->line("   🔹 {$rawMaterial->name}: -{$consumedGram} گرم (مصرف تولید) → موجودی: {$rawMaterial->stock} گرم");
                }
            }

            DB::commit();

            $this->info('✅ موجودی مواد اولیه با موفقیت بازنشانی شد.');
            $this->info('📌 موجودی نهایی:');

            foreach (RawMaterial::all() as $material) {
                $this->line("   🔹 {$material->name}: {$material->stock} گرم");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ خطا: ' . $e->getMessage());
            return 1;
        }
    }
}