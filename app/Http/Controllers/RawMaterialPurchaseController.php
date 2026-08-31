<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class RawMaterialPurchaseController extends Controller
{
    protected function cleanNumber($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return preg_replace('/[^0-9.]/', '', $value);
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
        $cleanedData = $request->all();
        $cleanedData['total_transport_cost'] = $this->cleanNumber($request->total_transport_cost);

        if (isset($cleanedData['items']) && is_array($cleanedData['items'])) {
            foreach ($cleanedData['items'] as $key => $item) {
                $cleanedData['items'][$key]['quantity'] = $this->cleanNumber($item['quantity'] ?? 0);
                $cleanedData['items'][$key]['total_price'] = $this->cleanNumber($item['total_price'] ?? 0);
                $cleanedData['items'][$key]['unit'] = $item['unit'] ?? 'kg';
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
            'items.*.unit' => 'required|in:kg,ton',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        DB::beginTransaction();

        try {
            $purchase = RawMaterialPurchase::create([
                'purchase_date' => $gregorianDate,
                'supplier' => $request->supplier,
                'total_transport_cost' => $request->total_transport_cost ?? 0,
            ]);

            foreach ($request->items as $item) {
                $quantity = (float) $item['quantity'];
                $unit = $item['unit'];

                if ($unit === 'ton') {
                    $quantityInGram = $quantity * 1000000;
                } else {
                    $quantityInGram = $quantity * 1000;
                }

                $purchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $quantityInGram,
                    'total_price' => $item['total_price'],
                    'unit' => $unit,
                ]);

                // به‌روزرسانی موجودی بر اساس محاسبه واقعی
                $rawMaterial = RawMaterial::find($item['raw_material_id']);
                if ($rawMaterial) {
                    $rawMaterial->refreshStock(); // این متد موجودی را بر اساس خرید و مصرف محاسبه می‌کند
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت خرید: ' . $e->getMessage()]);
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
        $cleanedData = $request->all();
        $cleanedData['total_transport_cost'] = $this->cleanNumber($request->total_transport_cost);

        if (isset($cleanedData['items']) && is_array($cleanedData['items'])) {
            foreach ($cleanedData['items'] as $key => $item) {
                $cleanedData['items'][$key]['quantity'] = $this->cleanNumber($item['quantity'] ?? 0);
                $cleanedData['items'][$key]['total_price'] = $this->cleanNumber($item['total_price'] ?? 0);
                $cleanedData['items'][$key]['unit'] = $item['unit'] ?? 'kg';
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
            'items.*.unit' => 'required|in:kg,ton',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['purchase_date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        DB::beginTransaction();

        try {
            // حذف آیتم‌های قبلی و به‌روزرسانی خرید
            $rawMaterialPurchase->items()->delete();

            $rawMaterialPurchase->update([
                'purchase_date' => $gregorianDate,
                'supplier' => $request->supplier,
                'total_transport_cost' => $request->total_transport_cost ?? 0,
            ]);

            foreach ($request->items as $item) {
                $quantity = (float) $item['quantity'];
                $unit = $item['unit'];

                if ($unit === 'ton') {
                    $quantityInGram = $quantity * 1000000;
                } else {
                    $quantityInGram = $quantity * 1000;
                }

                $rawMaterialPurchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $quantityInGram,
                    'total_price' => $item['total_price'],
                    'unit' => $unit,
                ]);

                // به‌روزرسانی موجودی
                $rawMaterial = RawMaterial::find($item['raw_material_id']);
                if ($rawMaterial) {
                    $rawMaterial->refreshStock();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش خرید: ' . $e->getMessage()]);
        }

        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت ویرایش شد.');
    }

    public function destroy(RawMaterialPurchase $rawMaterialPurchase)
    {
        DB::beginTransaction();

        try {
            $rawMaterialPurchase->delete(); // حذف خرید و آیتم‌ها (با cascade)

            // به‌روزرسانی موجودی برای تمام مواد اولیه‌ای که در این خرید بودند
            $rawMaterialIds = $rawMaterialPurchase->items()->pluck('raw_material_id')->unique();
            foreach ($rawMaterialIds as $id) {
                $rawMaterial = RawMaterial::find($id);
                if ($rawMaterial) {
                    $rawMaterial->refreshStock();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('raw-material-purchases.index')
                ->with('error', 'خطا در حذف خرید: ' . $e->getMessage());
        }

        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت حذف شد.');
    }
}