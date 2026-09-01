<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Packaging;
use App\Models\RawMaterial;
use App\Models\WaxInventory;
use App\Models\Glaze1300Inventory;
use App\Models\WarehouseInventory;
use App\Models\ShoulderInventory;
use App\Models\WasteMumInventory;
use App\Models\OpeningInventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function rawMaterialsStock()
    {
        $materials = RawMaterial::orderBy('name')->get();
        return view('inventory.raw-materials', compact('materials'));
    }

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

    public function packagingStock()
    {
        $packagings = Packaging::orderBy('type')->orderBy('name')->get();
        return view('inventory.packaging-stock', compact('packagings'));
    }

    public function warehouse(Request $request)
    {
        $query = Product::where('status', 1);

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $warehouse = WarehouseInventory::where('product_id', $product->id)->first();
            $stock = $warehouse ? $warehouse->stock : 0;

            $inventories[] = [
                'product' => $product,
                'stock' => $stock,
            ];
        }

        return view('inventory.warehouse', compact('inventories'));
    }

    public function shoulder(Request $request)
    {
        $query = Product::where('status', 1);

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $shoulder = ShoulderInventory::where('product_id', $product->id)->first();
            $stock = $shoulder ? $shoulder->stock : 0;

            $inventories[] = [
                'product' => $product,
                'stock' => $stock,
            ];
        }

        return view('inventory.shoulder', compact('inventories'));
    }

    public function allStocks()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $stocks = collect();

        foreach ($products as $product) {
            $stocks->push((object) [
                'product' => $product,
                'opening' => OpeningInventory::where('product_id', $product->id)->sum('quantity'),
                'raw' => ShuttleFiring::getRawStock($product->id),
                'wax' => $product->waxInventory->stock ?? 0,
                'glaze1300' => $product->glaze1300Inventory->stock ?? 0,
                'warehouse' => $product->warehouseInventory->stock ?? 0,
                'shoulder' => $product->shoulderInventory->stock ?? 0,
                'waste_mum' => $product->wasteMumInventory->stock ?? 0,
            ]);
        }

        return view('inventory.all-stocks', compact('stocks'));
    }
}