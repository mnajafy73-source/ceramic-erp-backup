<?php

namespace App\Observers;

use App\Models\Production;
use App\Models\RawMaterial;
use App\Models\Packaging;

class ProductionObserver
{
    /**
     * وقتی یک تولید جدید ثبت می‌شود، این متد اجرا می‌شود.
     */
    public function created(Production $production)
    {
        $product = $production->product;

        // ==========================================
        // ۱. مصرف مواد اولیه
        // ==========================================
        if ($product->weight && $product->formula_id) {
            // وزن کل مواد مصرفی به کیلوگرم
            $totalMaterialKg = ($production->quantity * $product->weight) / 1000;
            $formulaItems = $product->formula->items;

            foreach ($formulaItems as $item) {
                $consumedKg = ($totalMaterialKg * $item->percentage) / 100;
                $rawMaterial = RawMaterial::find($item->raw_material_id);
                if ($rawMaterial) {
                    $rawMaterial->stock -= $consumedKg;
                    $rawMaterial->save();
                }
            }
        }

        // ==========================================
        // ۲. مصرف کارتن
        // ==========================================
        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $carton->stock -= $cartonCount;
                $carton->save();
            }
        }

        // ==========================================
        // ۳. مصرف لایه
        // ==========================================
        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($production->quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $layer->stock -= $layerCount;
                $layer->save();
            }
        }
    }
}