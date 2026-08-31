<?php

namespace App\Observers;

use App\Models\Production;
use App\Models\RawMaterial;
use App\Models\Packaging;
use Illuminate\Support\Facades\Log;

class ProductionObserver
{
    /**
     * وقتی تولید جدید ثبت می‌شود → کسر مواد اولیه
     */
    public function created(Production $production)
    {
        Log::info('🔵 ProductionObserver: created called for production ID: ' . $production->id);
        $this->subtractMaterials($production);
    }

    /**
     * وقتی تولید ویرایش می‌شود → بازگشت قبلی + کسر جدید
     */
    public function updated(Production $production)
    {
        Log::info('🔵 ProductionObserver: updated called for production ID: ' . $production->id);
        $original = $production->getOriginal();
        $oldProduction = new Production($original);
        $this->addMaterials($oldProduction);
        $this->subtractMaterials($production);
    }

    /**
     * وقتی تولید حذف می‌شود → بازگشت مواد اولیه
     */
    public function deleted(Production $production)
    {
        Log::info('🔵 ProductionObserver: deleted called for production ID: ' . $production->id);
        $this->addMaterials($production);
    }

    // ============================================================
    //  متدهای کمکی (محاسبه مستقیم به گرم)
    // ============================================================

    private function subtractMaterials(Production $production)
    {
        Log::info('🔵 subtractMaterials called for production ID: ' . $production->id);

        $product = $production->product;

        if (!$product) {
            Log::error('❌ Product not found for production ID: ' . $production->id);
            return;
        }

        Log::info('📦 Product: ' . $product->name . ' (ID: ' . $product->id . ')');
        Log::info('📊 Weight: ' . $product->weight . ' (گرم), formula_id: ' . $product->formula_id);

        // ============================================================
        // ۱. کسر مواد اولیه (محاسبه مستقیم به گرم)
        // ============================================================
        if ($product->weight && $product->formula_id) {
            Log::info('✅ Weight and formula exist, proceeding with material subtraction');

            // ✅ وزن کل بر حسب گرم (مستقیم بدون تبدیل به کیلوگرم)
            $totalMaterialGram = $production->quantity * $product->weight;

            Log::info('📐 Total material gram: ' . $totalMaterialGram . ' گرم');

            $formulaItems = $product->formula->items;

            if ($formulaItems->isEmpty()) {
                Log::warning('⚠️ Formula has no items for product ID: ' . $product->id);
            } else {
                Log::info('📋 Formula has ' . $formulaItems->count() . ' items');

                foreach ($formulaItems as $item) {
                    // ✅ مصرف هر ماده به گرم (مستقیم)
                    $consumedGram = ($totalMaterialGram * $item->percentage) / 100;
                    Log::info('🔹 Raw material ID: ' . $item->raw_material_id . ', Percentage: ' . $item->percentage . '%, Consumed: ' . $consumedGram . ' گرم');

                    $rawMaterial = RawMaterial::find($item->raw_material_id);
                    if ($rawMaterial) {
                        $oldStock = $rawMaterial->stock;
                        $rawMaterial->stock -= $consumedGram;
                        $rawMaterial->save();
                        Log::info('✅ Raw material "' . $rawMaterial->name . '" stock: ' . $oldStock . ' → ' . $rawMaterial->stock);
                    } else {
                        Log::error('❌ Raw material not found for ID: ' . $item->raw_material_id);
                    }
                }
            }
        } else {
            Log::warning('⚠️ SKIP: weight or formula_id is missing. Weight: ' . $product->weight . ', formula_id: ' . $product->formula_id);
        }

        // ============================================================
        // ۲. کسر کارتن (بدون تغییر)
        // ============================================================
        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $oldStock = $carton->stock;
                $carton->stock -= $cartonCount;
                $carton->save();
                Log::info('📦 Carton "' . $carton->name . '" stock: ' . $oldStock . ' → ' . $carton->stock);
            }
        }

        // ============================================================
        // ۳. کسر لایه (بدون تغییر)
        // ============================================================
        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $oldStock = $layer->stock;
                $layer->stock -= $layerCount;
                $layer->save();
                Log::info('📦 Layer "' . $layer->name . '" stock: ' . $oldStock . ' → ' . $layer->stock);
            }
        }
    }

    private function addMaterials(Production $production)
    {
        Log::info('🔵 addMaterials called for production ID: ' . $production->id);

        $product = $production->product;

        if (!$product) {
            Log::error('❌ Product not found for production ID: ' . $production->id);
            return;
        }

        // ============================================================
        // ۱. بازگشت مواد اولیه (محاسبه مستقیم به گرم)
        // ============================================================
        if ($product->weight && $product->formula_id) {
            $totalMaterialGram = $production->quantity * $product->weight;
            $formulaItems = $product->formula->items;

            foreach ($formulaItems as $item) {
                $consumedGram = ($totalMaterialGram * $item->percentage) / 100;
                $rawMaterial = RawMaterial::find($item->raw_material_id);
                if ($rawMaterial) {
                    $oldStock = $rawMaterial->stock;
                    $rawMaterial->stock += $consumedGram;
                    $rawMaterial->save();
                    Log::info('↩️ Raw material "' . $rawMaterial->name . '" stock: ' . $oldStock . ' → ' . $rawMaterial->stock);
                }
            }
        }

        // ============================================================
        // ۲. بازگشت کارتن (بدون تغییر)
        // ============================================================
        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $carton->stock += $cartonCount;
                $carton->save();
            }
        }

        // ============================================================
        // ۳. بازگشت لایه (بدون تغییر)
        // ============================================================
        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $layer->stock += $layerCount;
                $layer->save();
            }
        }
    }
}