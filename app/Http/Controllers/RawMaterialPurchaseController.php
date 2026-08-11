<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class RawMaterialPurchaseController extends Controller
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
        $purchases = RawMaterialPurchase::with('items.rawMaterial')
            ->orderBy('purchase_date', 'desc')
            ->get();
        return view('raw-material-purchases.index', compact('purchases'));
    }

    public function create()
    {
        $materials = RawMaterial::orderBy('name')->get();
        return view('raw-material-purchases.create', compact('materials'));
    }

    public function store(Request $request)
    {
        // پاکسازی کاماها
        $cleanedData = $request->all();
        $cleanedData['total_transport_cost'] = $this->cleanNumber($request->total_transport_cost);

        if (isset($cleanedData['items']) && is_array($cleanedData['items'])) {
            foreach ($cleanedData['items'] as $key => $item) {
                $cleanedData['items'][$key]['quantity'] = $this->cleanNumber($item['quantity'] ?? 0);
                $cleanedData['items'][$key]['total_price'] = $this->cleanNumber($item['total_price'] ?? 0);
            }
        }
        $request->merge($cleanedData);

        // اعتبارسنجی
        $validated = $request->validate([
            'purchase_date' => 'required|string',
            'supplier' => 'nullable|string|max:255',
            'total_transport_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $purchase = RawMaterialPurchase::create([
            'purchase_date' => $gregorianDate,
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $purchase->items()->create($item);

            $rawMaterial = RawMaterial::find($item['raw_material_id']);
            if ($rawMaterial) {
                $rawMaterial->stock += $item['quantity'];
                $rawMaterial->save();
            }
        }

        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت ثبت شد.');
    }

    public function show(RawMaterialPurchase $rawMaterialPurchase)
    {
        $rawMaterialPurchase->load('items.rawMaterial');
        return view('raw-material-purchases.show', compact('rawMaterialPurchase'));
    }

    public function edit(RawMaterialPurchase $rawMaterialPurchase)
    {
        $materials = RawMaterial::orderBy('name')->get();
        $rawMaterialPurchase->load('items.rawMaterial');
        return view('raw-material-purchases.edit', compact('rawMaterialPurchase', 'materials'));
    }

    public function update(Request $request, RawMaterialPurchase $rawMaterialPurchase)
    {
        // پاکسازی کاماها
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
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        // برگرداندن موجودی قبلی
        foreach ($rawMaterialPurchase->items as $item) {
            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $rawMaterial->stock -= $item->quantity;
                $rawMaterial->save();
            }
        }

        $rawMaterialPurchase->items()->delete();

        $rawMaterialPurchase->update([
            'purchase_date' => $gregorianDate,
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $rawMaterialPurchase->items()->create($item);

            $rawMaterial = RawMaterial::find($item['raw_material_id']);
            if ($rawMaterial) {
                $rawMaterial->stock += $item['quantity'];
                $rawMaterial->save();
            }
        }

        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت ویرایش شد.');
    }

    public function destroy(RawMaterialPurchase $rawMaterialPurchase)
    {
        $rawMaterialPurchase->delete();
        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت حذف شد.');
    }
}