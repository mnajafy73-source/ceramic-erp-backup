<?php

namespace App\Http\Controllers;

use App\Models\OpeningInventory;
use App\Models\Product;
use Illuminate\Http\Request;

class OpeningInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = OpeningInventory::with('product');

        // جستجو بر اساس محصول
        if ($request->filled('search')) {
            $query->where('product_id', $request->search);
        }

        $inventories = $query->get();

        return view('opening-inventories.index', compact('inventories'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        return view('opening-inventories.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'date' => 'nullable|string',
        ]);

        $exists = OpeningInventory::where('product_id', $request->product_id)->exists();
        if ($exists) {
            return redirect()->route('opening-inventories.index')
                ->with('error', 'برای این محصول قبلاً موجودی اولیه ثبت شده است.');
        }

        OpeningInventory::create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'date' => $request->date ?? jdate()->format('Y/m/d'),
        ]);

        return redirect()->route('opening-inventories.index')
            ->with('success', 'موجودی اولیه با موفقیت ثبت شد.');
    }

    public function edit(OpeningInventory $openingInventory)
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        return view('opening-inventories.edit', compact('openingInventory', 'products'));
    }

    public function update(Request $request, OpeningInventory $openingInventory)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
            'date' => 'nullable|string',
        ]);

        $openingInventory->update([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'date' => $request->date ?? jdate()->format('Y/m/d'),
        ]);

        return redirect()->route('opening-inventories.index')
            ->with('success', 'موجودی اولیه با موفقیت ویرایش شد.');
    }

    public function destroy(OpeningInventory $openingInventory)
    {
        $openingInventory->delete();
        return redirect()->route('opening-inventories.index')
            ->with('success', 'موجودی اولیه با موفقیت حذف شد.');
    }
}