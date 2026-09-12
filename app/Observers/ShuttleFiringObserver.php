<?php

namespace App\Observers;

use App\Models\ShuttleFiring;
use App\Models\Packaging;
use App\Helpers\ImportFlag;

class ShuttleFiringObserver
{
    public function created(ShuttleFiring $shuttleFiring)
    {
        // ✅ اگه داریم واردات می‌کنیم، Observer کاری نکنه
        if (ImportFlag::$isImporting) return;

        if ($shuttleFiring->is_packaged && $shuttleFiring->output_quantity > 0) {
            $this->updatePackaging($shuttleFiring, 'subtract');
        }
    }

    public function updated(ShuttleFiring $shuttleFiring)
    {
        if (ImportFlag::$isImporting) return;

        $original = $shuttleFiring->getOriginal();
        $oldPackaged = $original['is_packaged'] ?? false;
        $oldQuantity = $original['output_quantity'] ?? 0;
        $oldProductId = $original['product_id'] ?? null;

        $newPackaged = $shuttleFiring->is_packaged;
        $newQuantity = $shuttleFiring->output_quantity;
        $newProductId = $shuttleFiring->product_id;

        if ($oldPackaged && $oldQuantity > 0) {
            $oldProduct = \App\Models\Product::find($oldProductId);
            if ($oldProduct) {
                $this->updatePackagingForProduct($oldProduct, $oldQuantity, 'add');
            }
        }

        if ($newPackaged && $newQuantity > 0) {
            $this->updatePackagingForProduct($shuttleFiring->product, $newQuantity, 'subtract');
        }
    }

    public function deleted(ShuttleFiring $shuttleFiring)
    {
        if (ImportFlag::$isImporting) return;

        $shuttleFiring->load('product');
        if ($shuttleFiring->is_packaged && $shuttleFiring->output_quantity > 0) {
            $this->updatePackaging($shuttleFiring, 'add');
        }
    }

    private function updatePackaging(ShuttleFiring $shuttleFiring, $operation)
    {
        $this->updatePackagingForProduct($shuttleFiring->product, $shuttleFiring->output_quantity, $operation);
    }

    private function updatePackagingForProduct($product, $quantity, $operation)
    {
        if (!$product || $quantity <= 0) {
            return;
        }

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                if ($operation === 'subtract') {
                    $carton->stock -= $cartonCount;
                } else {
                    $carton->stock += $cartonCount;
                }
                $carton->save();
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                if ($operation === 'subtract') {
                    $layer->stock -= $layerCount;
                } else {
                    $layer->stock += $layerCount;
                }
                $layer->save();
            }
        }
    }
}