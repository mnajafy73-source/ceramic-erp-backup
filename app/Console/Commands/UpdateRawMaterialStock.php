<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RawMaterial;
use App\Models\Production;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateRawMaterialStock extends Command
{
    protected $signature = 'raw-material:update-stock';
    protected $description = 'به‌روزرسانی موجودی مواد اولیه بر اساس وزن فعلی محصولات';

    public function handle()
    {
        $this->info('🔄 شروع به‌روزرسانی موجودی مواد اولیه...');

        DB::beginTransaction();

        try {
            // ۱. تنظیم موجودی مواد اولیه به صفر
            RawMaterial::query()->update(['stock' => 0]);
            $this->info('✅ موجودی مواد اولیه به صفر تنظیم شد.');

            // ۲. دریافت تمام تولیدات با محصول و فرمول
            $productions = Production::with('product.formula.items.rawMaterial')
                ->where('stage', 'تولید')
                ->get();

            $this->info('📊 تعداد تولیدات: ' . $productions->count());

            $materialConsumption = [];

            foreach ($productions as $production) {
                $product = $production->product;
                if (!$product || !$product->weight || !$product->formula_id) {
                    continue;
                }

                // ✅ از وزن فعلی محصول استفاده می‌کنیم
                $weightInKg = $this->convertWeightToKg($product->weight);
                $totalMaterialKg = $production->quantity * $weightInKg;

                foreach ($product->formula->items as $item) {
                    $consumedKg = ($totalMaterialKg * $item->percentage) / 100;
                    $materialId = $item->raw_material_id;

                    if (!isset($materialConsumption[$materialId])) {
                        $materialConsumption[$materialId] = 0;
                    }
                    $materialConsumption[$materialId] += $consumedKg;
                }
            }

            // ۳. کسر از موجودی مواد اولیه
            foreach ($materialConsumption as $materialId => $consumedKg) {
                $rawMaterial = RawMaterial::find($materialId);
                if ($rawMaterial) {
                    $rawMaterial->stock -= $consumedKg;
                    $rawMaterial->save();
                    $this->line("   🔹 {$rawMaterial->name}: -{$consumedKg} کیلوگرم");
                }
            }

            DB::commit();

            $this->info('✅ موجودی مواد اولیه با موفقیت به‌روزرسانی شد.');
            $this->info('📌 مجموع مواد مصرفی از ' . count($materialConsumption) . ' نوع ماده اولیه.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ خطا: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function convertWeightToKg($weight)
    {
        return ($weight < 1000) ? $weight / 1000 : $weight;
    }
}