<?php

namespace App\Http\Controllers;

use App\Models\Packaging;
use App\Models\PackagingPurchase;
use App\Models\PackagingPurchaseItem;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class PackagingPurchaseController extends Controller
{
    /**
     * حذف کاما از اعداد ورودی (سطح دسترسی protected)
     */
    protected function cleanNumber($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return str_replace(',', '', $value);
    }

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
        $cleanedData = $request->all();
        $cleanedData['total_transport_cost'] = $this->cleanNumber($request->total_transport_cost);

        if (isset($cleanedData['items']) && is_array($cleanedData['items'])) {
            foreach ($cleanedData['items'] as $key => $item) {
                $cleanedData['items'][$key]['quantity'] = $this->cleanNumber($item['quantity'] ?? 0);
                $cleanedData['items'][$key]['total_price'] = $this->cleanNumber($item['total_price'] ?? 0);
            }
        }
        $request->merge($cleanedData);

        $validated = $request->validate([
            'purchase_date' => 'required|string',
            'supplier' => 'nullable|string|max:255',
            'total_transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.packaging_id' => 'required|exists:packagings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $purchase = PackagingPurchase::create([
            'purchase_date' => $gregorianDate,
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $purchase->items()->create($item);

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
        $cleanedData = $request->all();
        $cleanedData['total_transport_cost'] = $this->cleanNumber($request->total_transport_cost);

        if (isset($cleanedData['items']) && is_array($cleanedData['items'])) {
            foreach ($cleanedData['items'] as $key => $item) {
                $cleanedData['items'][$key]['quantity'] = $this->cleanNumber($item['quantity'] ?? 0);
                $cleanedData['items'][$key]['total_price'] = $this->cleanNumber($item['total_price'] ?? 0);
            }
        }
        $request->merge($cleanedData);

        $validated = $request->validate([
            'purchase_date' => 'required|string',
            'supplier' => 'nullable|string|max:255',
            'total_transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.packaging_id' => 'required|exists:packagings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        // برگرداندن موجودی قبلی
        foreach ($packagingPurchase->items as $item) {
            $packaging = Packaging::find($item->packaging_id);
            if ($packaging) {
                $packaging->stock -= $item->quantity;
                $packaging->save();
            }
        }

        $packagingPurchase->items()->delete();

        $packagingPurchase->update([
            'purchase_date' => $gregorianDate,
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $packagingPurchase->items()->create($item);

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
        $packagingPurchase->delete();
        return redirect()->route('packaging-purchases.index')
            ->with('success', 'خرید کارتن/لایه با موفقیت حذف شد.');
    }
}