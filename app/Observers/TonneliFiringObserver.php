<?php

namespace App\Observers;

use App\Models\TonneliFiring;
use App\Models\Packaging;

class TonneliFiringObserver
{
    /**
     * بعد از ثبت پخت تونلی
     */
    public function created(TonneliFiring $tonneliFiring)
    {
        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $item) {
            if ($item->is_packaged && $item->output_quantity > 0) {
                $this->updatePackagingForItem($item, 'subtract');
            }
        }
    }

    /**
     * بعد از ویرایش پخت تونلی
     */
    public function updated(TonneliFiring $tonneliFiring)
    {
        // آیتم‌های قدیمی را از دیتابیس می‌خوانیم
        $oldItems = $tonneliFiring->items()->with('product')->get();

        // ۱. برگرداندن موجودی آیتم‌های قدیمی
        foreach ($oldItems as $oldItem) {
            if ($oldItem->is_packaged && $oldItem->output_quantity > 0) {
                $this->updatePackagingForItem($oldItem, 'add');
            }
        }

        // ۲. کسر موجودی آیتم‌های جدید (که در رکورد فعلی بارگذاری شده‌اند)
        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $newItem) {
            if ($newItem->is_packaged && $newItem->output_quantity > 0) {
                $this->updatePackagingForItem($newItem, 'subtract');
            }
        }
    }

    /**
     * قبل از حذف پخت تونلی (با استفاده از رویداد deleting)
     */
    public function deleting(TonneliFiring $tonneliFiring)
    {
        // بارگذاری آیتم‌ها با رابطه product (قبل از حذف آیتم‌ها)
        $tonneliFiring->load('items.product');
        foreach ($tonneliFiring->items as $item) {
            if ($item->is_packaged && $item->output_quantity > 0) {
                $this->updatePackagingForItem($item, 'add');
            }
        }
    }

    /**
     * متد کمکی برای کسر یا افزودن موجودی کارتن/لایه برای یک آیتم
     */
    private function updatePackagingForItem($item, $operation)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;

        if (!$product || $quantity <= 0) {
            return;
        }

        // کارتن
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

        // لایه
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