<?php

namespace App\Traits;

trait HasUndo
{
    public static function bootHasUndo()
    {
        static::deleting(function ($model) {
            // اگر عملیات گروهی در حال اجراست، از ذخیره تکی جلوگیری کن
            if (session('undo_record') && isset(session('undo_record')['multiple']) && session('undo_record')['multiple'] === true) {
                return;
            }

            $data = $model->getAttributes();
            unset($data['id'], $data['created_at'], $data['updated_at']);

            // ذخیره اطلاعات اضافی برای مدل‌هایی که آیتم دارند (مثل خرید مواد)
            $extraData = [];

            // اگر مدل دارای آیتم‌های خرید است (RawMaterialPurchase)
            if ($model instanceof \App\Models\RawMaterialPurchase) {
                $model->load('items');
                $itemsData = [];
                foreach ($model->items as $item) {
                    $itemData = $item->getAttributes();
                    unset($itemData['id'], $itemData['purchase_id'], $itemData['created_at'], $itemData['updated_at']);
                    $itemsData[] = $itemData;
                }
                $extraData['items'] = $itemsData;
            }

            // اگر مدل دارای محصولات است (برای فروش)
            if (method_exists($model, 'products')) {
                $productsData = [];
                foreach ($model->products as $product) {
                    $productData = $product->getAttributes();
                    unset($productData['id'], $productData['sale_id'], $productData['informal_sale_id'], $productData['created_at'], $productData['updated_at']);
                    $productsData[] = $productData;
                }
                $extraData['products'] = $productsData;
            }

            session()->put('undo_record', [
                'class' => get_class($model),
                'data'   => $data,
                'extra'  => $extraData,
            ]);
        });
    }
}