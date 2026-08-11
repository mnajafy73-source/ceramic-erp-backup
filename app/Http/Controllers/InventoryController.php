<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Packaging;
use App\Models\RawMaterial;
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
     * موجودی خام
     */
    public function raw(Request $request)
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
                'stock' => ShuttleFiring::getRawStock($product->id),
            ];
        }

        return view('inventory.raw', compact('inventories'));
    }

    /**
     * موجودی موم (۹۰۰ درجه)
     */
    public function mum(Request $request)
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
                'stock' => ShuttleFiring::getMumStock($product->id),
            ];
        }

        return view('inventory.mum', compact('inventories'));
    }

    /**
     * موجودی ۱۳۰۰ درجه
     */
    public function glaze1300(Request $request)
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
                'stock' => ShuttleFiring::getGlaze1300Stock($product->id),
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