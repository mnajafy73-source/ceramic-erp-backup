<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterialPurchaseItem;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class RawMaterialPurchaseController extends Controller
{
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
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        $jalaliDate = Jalalian::fromFormat('Y/m/d', $request->purchase_date);
        $gregorianDate = $jalaliDate->toCarbon();

        $totalItemsCount = count($request->items);

        $purchase = RawMaterialPurchase::create([
            'purchase_date' => $gregorianDate->format('Y-m-d'),
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $transportShare = ($totalItemsCount > 0) ? ($request->total_transport_cost / $totalItemsCount) : 0;
            $pricePerGram = ($item['total_price'] + $transportShare) / ($item['quantity'] * 1000);

            $purchase->items()->create([
                'raw_material_id' => $item['raw_material_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'price_per_gram' => $pricePerGram,
            ]);

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
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        foreach ($rawMaterialPurchase->items as $item) {
            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $rawMaterial->stock -= $item->quantity;
                $rawMaterial->save();
            }
        }

        $rawMaterialPurchase->items()->delete();

        $jalaliDate = Jalalian::fromFormat('Y/m/d', $request->purchase_date);
        $gregorianDate = $jalaliDate->toCarbon();

        $totalItemsCount = count($request->items);

        $rawMaterialPurchase->update([
            'purchase_date' => $gregorianDate->format('Y-m-d'),
            'supplier' => $request->supplier,
            'total_transport_cost' => $request->total_transport_cost ?? 0,
        ]);

        foreach ($request->items as $item) {
            $transportShare = ($totalItemsCount > 0) ? ($request->total_transport_cost / $totalItemsCount) : 0;
            $pricePerGram = ($item['total_price'] + $transportShare) / ($item['quantity'] * 1000);

            $rawMaterialPurchase->items()->create([
                'raw_material_id' => $item['raw_material_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'price_per_gram' => $pricePerGram,
            ]);

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
        foreach ($rawMaterialPurchase->items as $item) {
            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $rawMaterial->stock -= $item->quantity;
                $rawMaterial->save();
            }
        }

        $rawMaterialPurchase->delete();

        return redirect()->route('raw-material-purchases.index')
            ->with('success', 'خرید مواد با موفقیت حذف شد.');
    }
}