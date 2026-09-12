<?php

namespace App\Observers;

use App\Models\TonneliFiring;
use App\Models\Packaging;
use App\Helpers\ImportFlag;

class TonneliFiringObserver
{
    public function created(TonneliFiring $tonneliFiring)
    {
        if (ImportFlag::$isImporting) return;

        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $item) {
            if ($item->is_packaged && $item->output_quantity > 0) {
                $this->updatePackagingForItem($item, 'subtract');
            }
        }
    }

    public function updated(TonneliFiring $tonneliFiring)
    {
        if (ImportFlag::$isImporting) return;

        $oldItems = $tonneliFiring->items()->with('product')->get();

        foreach ($oldItems as $oldItem) {
            if ($oldItem->is_packaged && $oldItem->output_quantity > 0) {
                $this->updatePackagingForItem($oldItem, 'add');
            }
        }

        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $newItem) {
            if ($newItem->is_packaged && $newItem->output_quantity > 0) {
                $this->updatePackagingForItem($newItem, 'subtract');
            }
        }
    }

    public function deleting(TonneliFiring $tonneliFiring)
    {
        if (ImportFlag::$isImporting) return;

        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $item) {
            if ($item->is_packaged && $item->output_quantity > 0) {
                $this->updatePackagingForItem($item, 'add');
            }
        }
    }

    private function updatePackagingForItem($item, $operation)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;

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