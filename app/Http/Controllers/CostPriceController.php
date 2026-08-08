<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\Packaging;
use Illuminate\Http\Request;

class CostPriceController extends Controller
{
    public function index()
    {
        // ===== آخرین قیمت هر ماده اولیه =====
        $rawMaterials = RawMaterial::all();
        $lastRawMaterialPurchases = [];

        foreach ($rawMaterials as $material) {
            $lastItem = $material->purchaseItems()
                ->with('purchase')
                ->whereHas('purchase')
                ->orderBy('purchase_date', 'desc')
                ->first();

            if ($lastItem) {
                $lastRawMaterialPurchases[] = (object) [
                    'raw_material' => $material,
                    'price_per_gram' => $lastItem->price_per_gram,
                    'quantity' => $lastItem->quantity,
                    'purchase_date' => $lastItem->purchase->purchase_date,
                ];
            }
        }

        // ===== آخرین قیمت هر کارتن/لایه =====
        $packagings = Packaging::all();
        $lastPackagingPurchases = [];

        foreach ($packagings as $packaging) {
            $lastItem = $packaging->purchaseItems()
                ->with('purchase')
                ->whereHas('purchase')
                ->orderBy('purchase_date', 'desc')
                ->first();

            if ($lastItem) {
                $lastPackagingPurchases[] = (object) [
                    'packaging' => $packaging,
                    'price_per_unit' => $lastItem->price_per_unit,
                    'quantity' => $lastItem->quantity,
                    'purchase_date' => $lastItem->purchase->purchase_date,
                ];
            }
        }

        return view('cost-price.index', compact('lastRawMaterialPurchases', 'lastPackagingPurchases'));
    }
}