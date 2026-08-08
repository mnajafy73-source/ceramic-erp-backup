<?php

namespace App\Http\Controllers;

use App\Models\Packaging;
use App\Models\PackagingPurchase;
use App\Models\PackagingPurchaseItem;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class PackagingPurchaseController extends Controller
{
    public function index()
    {
        $purchases = PackagingPurchase::with('items.packaging')
            ->orderBy('purchase_date', 'desc')
            ->get();
        return view('packaging-purchases.index', compact('purchases'));
    }

    public function create()
    {
        $packagings = Packaging::orderBy('name')->get();
        return view('packaging-purchases.create', compact('packagings'));
    }

    public function store(Request $request)
    {
        // پاکسازی کاماها از ورودی‌های عددی
        $request->merge([
            'total_transport_cost' => $this->cleanNumber($request->total_transport_cost),
        ]);

        $items = $request->items;
        foreach ($items as $key => $item) {
            $items[$key]['quantity'] = $this->cleanNumber($item['quantity']);
            $items[$key]['total_price'] = $this->cleanNumber($item['total_price']);
        }
        $request->merge(['items' => $items]);

        $request->validate([
            'purchase_date' => 'required|string',
            'supplier' => 'nullable|string|max:255',
            'total_transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.packaging_id' => 'required|exists:packagings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        $jalaliDate = Jalalian::fromFormat('Y/m/d', $request->purchase_date);
        $gregorianDate = $jalaliDate->toCarbon();

        $totalItemsCount = count($request->items);

        $purchase = PackagingPurchase::create([
            'purchase_date' => $gregorianDate->format('Y-m-d'),
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $transportShare = ($totalItemsCount > 0) ? ($request->total_transport_cost / $totalItemsCount) : 0;
            $pricePerUnit = ($item['total_price'] + $transportShare) / $item['quantity'];

            $purchase->items()->create([
                'packaging_id' => $item['packaging_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'price_per_unit' => $pricePerUnit,
            ]);

            $packaging = Packaging::find($item['packaging_id']);
            if ($packaging) {
                $packaging->stock += $item['quantity'];
                $packaging->save();
            }
        }

        return redirect()->route('packaging-purchases.index')
            ->with('success', 'خرید کارتن/لایه با موفقیت ثبت شد.');
    }

    public function show(PackagingPurchase $packagingPurchase)
    {
        $packagingPurchase->load('items.packaging');
        return view('packaging-purchases.show', compact('packagingPurchase'));
    }

    public function edit(PackagingPurchase $packagingPurchase)
    {
        $packagings = Packaging::orderBy('name')->get();
        $packagingPurchase->load('items.packaging');
        return view('packaging-purchases.edit', compact('packagingPurchase', 'packagings'));
    }

    public function update(Request $request, PackagingPurchase $packagingPurchase)
    {
        $request->merge([
            'total_transport_cost' => $this->cleanNumber($request->total_transport_cost),
        ]);

        $items = $request->items;
        foreach ($items as $key => $item) {
            $items[$key]['quantity'] = $this->cleanNumber($item['quantity']);
            $items[$key]['total_price'] = $this->cleanNumber($item['total_price']);
        }
        $request->merge(['items' => $items]);

        $request->validate([
            'purchase_date' => 'required|string',
            'supplier' => 'nullable|string|max:255',
            'total_transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.packaging_id' => 'required|exists:packagings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        foreach ($packagingPurchase->items as $item) {
            $packaging = Packaging::find($item->packaging_id);
            if ($packaging) {
                $packaging->stock -= $item->quantity;
                $packaging->save();
            }
        }

        $packagingPurchase->items()->delete();

        $jalaliDate = Jalalian::fromFormat('Y/m/d', $request->purchase_date);
        $gregorianDate = $jalaliDate->toCarbon();

        $totalItemsCount = count($request->items);

        $packagingPurchase->update([
            'purchase_date' => $gregorianDate->format('Y-m-d'),
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $transportShare = ($totalItemsCount > 0) ? ($request->total_transport_cost / $totalItemsCount) : 0;
            $pricePerUnit = ($item['total_price'] + $transportShare) / $item['quantity'];

            $packagingPurchase->items()->create([
                'packaging_id' => $item['packaging_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'price_per_unit' => $pricePerUnit,
            ]);

            $packaging = Packaging::find($item['packaging_id']);
            if ($packaging) {
                $packaging->stock += $item['quantity'];
                $packaging->save();
            }
        }

        return redirect()->route('packaging-purchases.index')
            ->with('success', 'خرید کارتن/لایه با موفقیت ویرایش شد.');
    }

    public function destroy(PackagingPurchase $packagingPurchase)
    {
        foreach ($packagingPurchase->items as $item) {
            $packaging = Packaging::find($item->packaging_id);
            if ($packaging) {
                $packaging->stock -= $item->quantity;
                $packaging->save();
            }
        }

        $packagingPurchase->delete();

        return redirect()->route('packaging-purchases.index')
            ->with('success', 'خرید کارتن/لایه با موفقیت حذف شد.');
    }
}