<?php

namespace App\Observers;

use App\Models\PackagingPurchase;

class PackagingPurchaseObserver
{
    /**
     * قبل از حذف خرید، موجودی کارتن/لایه را برگردان.
     */
    public function deleting(PackagingPurchase $purchase)
    {
        foreach ($purchase->items as $item) {
            $packaging = $item->packaging;
            if ($packaging) {
                $packaging->stock -= $item->quantity;
                $packaging->save();
            }
        }
    }
}