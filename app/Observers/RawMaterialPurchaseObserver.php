<?php

namespace App\Observers;

use App\Models\RawMaterialPurchase;

class RawMaterialPurchaseObserver
{
    public function deleting(RawMaterialPurchase $purchase)
    {
        $purchase->load('items.rawMaterial');

        foreach ($purchase->items as $item) {
            $rawMaterial = $item->rawMaterial;
            if ($rawMaterial) {
                $rawMaterial->stock -= $item->quantity;
                $rawMaterial->save();
            }
        }
    }
}