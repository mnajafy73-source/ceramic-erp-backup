<?php

namespace App\Observers;

use App\Models\RawMaterialPurchase;

class RawMaterialPurchaseObserver
{
    /**
     * قبل از حذف خرید، موجودی مواد اولیه را برگردان.
     */
    public function deleting(RawMaterialPurchase $purchase)
    {
        foreach ($purchase->items as $item) {
            $rawMaterial = $item->rawMaterial;
            if ($rawMaterial) {
                $rawMaterial->stock -= $item->quantity;
                $rawMaterial->save();
            }
        }
    }
}