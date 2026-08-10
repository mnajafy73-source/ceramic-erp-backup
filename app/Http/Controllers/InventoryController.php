<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShuttleFiring;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

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