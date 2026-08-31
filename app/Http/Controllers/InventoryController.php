<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Packaging;
use App\Models\RawMaterial;
use App\Models\WaxInventory;
use App\Models\Glaze1300Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    /**
     * موجودی مواد اولیه
     */
    public function rawMaterialsStock()
    {
        $materials = RawMaterial::orderBy('name')->get();
        return view('inventory.raw-materials', compact('materials'));
    }

    /**
     * ✅ موجودی خام (فقط محصولات والد - بدون فرزندان)
     */
    public function raw(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereNull('parent_product_id');

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => ShuttleFiring::getRawStock($product->id),
            ];
        }

        return view('inventory.raw', compact('inventories'));
    }

    /**
     * موجودی موم (۹۰۰ درجه) - فقط محصولات با موجودی > ۰
     */
    public function mum(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereHas('waxInventory', function ($q) {
                $q->where('stock', '>', 0);
            });

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => $product->waxInventory->stock ?? 0,
            ];
        }

        return view('inventory.mum', compact('inventories'));
    }

    /**
     * موجودی ۱۳۰۰ درجه - فقط محصولات با موجودی > ۰
     */
    public function glaze1300(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereHas('glaze1300Inventory', function ($q) {
                $q->where('stock', '>', 0);
            });

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => $product->glaze1300Inventory->stock ?? 0,
            ];
        }

        return view('inventory.glaze1300', compact('inventories'));
    }

    /**
     * موجودی کارتن و لایه
     */
    public function packagingStock()
    {
        $packagings = Packaging::orderBy('type')->orderBy('name')->get();
        return view('inventory.packaging-stock', compact('packagings'));
    }

    /**
     * موجودی انبار
     */
    public function warehouse(Request $request)
    {
        $query = Product::where('status', 1);

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => ShuttleFiring::getWarehouseStock($product->id),
            ];
        }

        return view('inventory.warehouse', compact('inventories'));
    }
}