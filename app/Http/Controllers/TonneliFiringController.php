<?php

namespace App\Http\Controllers;

use App\Models\TonneliFiring;
use App\Models\TonneliFiringItem;
use App\Models\Product;
use App\Models\Packaging;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class TonneliFiringController extends Controller
{
    public function index()
    {
        $firings = TonneliFiring::with('items.product')->latest('date')->paginate(15);
        return view('tonneli.index', compact('firings'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('tonneli.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.input_quantity' => 'nullable|numeric|min:0',
            'items.*.output_quantity' => 'nullable|numeric|min:0',
            'items.*.is_packaged' => 'nullable|boolean',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $firing = TonneliFiring::create([
                'date' => $gregorianDate,
            ]);

            foreach ($validated['items'] as $item) {
                $inputQty = $item['input_quantity'] ?? 0;
                $outputQty = $item['output_quantity'] ?? 0;
                $isPackaged = isset($item['is_packaged']) && $item['is_packaged'] ? 1 : 0;

                if ($inputQty == 0 && $outputQty == 0) {
                    continue;
                }

                $newItem = $firing->items()->create([
                    'product_id' => $item['product_id'],
                    'input_quantity' => $inputQty,
                    'output_quantity' => $outputQty,
                    'is_packaged' => $isPackaged,
                ]);

                if ($isPackaged && $outputQty > 0) {
                    $this->subtractPackagingForItem($newItem);
                }
            }

            DB::commit();
            return redirect()->route('tonneli.create')->with('success', 'پخت تونلی با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت پخت: ' . $e->getMessage()]);
        }
    }

    public function show(TonneliFiring $tonneli)
    {
        $tonneli->load('items.product');
        return view('tonneli.show', compact('tonneli'));
    }

    public function edit(TonneliFiring $tonneli)
    {
        $products = Product::where('status', true)->get();
        $tonneli->load('items');
        $tonneli->jalali_date = Jalalian::fromCarbon($tonneli->date)->format('Y/m/d');
        return view('tonneli.edit', compact('tonneli', 'products'));
    }

    public function update(Request $request, TonneliFiring $tonneli)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.input_quantity' => 'nullable|numeric|min:0',
            'items.*.output_quantity' => 'nullable|numeric|min:0',
            'items.*.is_packaged' => 'nullable|boolean',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $oldItems = $tonneli->items()->with('product')->get();
            foreach ($oldItems as $oldItem) {
                if ($oldItem->is_packaged && $oldItem->output_quantity > 0) {
                    $this->returnPackagingForItem($oldItem);
                }
            }

            $tonneli->items()->delete();
            $tonneli->update(['date' => $gregorianDate]);

            foreach ($validated['items'] as $item) {
                $inputQty = $item['input_quantity'] ?? 0;
                $outputQty = $item['output_quantity'] ?? 0;
                $isPackaged = isset($item['is_packaged']) && $item['is_packaged'] ? 1 : 0;

                if ($inputQty == 0 && $outputQty == 0) {
                    continue;
                }

                $newItem = $tonneli->items()->create([
                    'product_id' => $item['product_id'],
                    'input_quantity' => $inputQty,
                    'output_quantity' => $outputQty,
                    'is_packaged' => $isPackaged,
                ]);

                if ($isPackaged && $outputQty > 0) {
                    $this->subtractPackagingForItem($newItem);
                }
            }

            DB::commit();
            return redirect()->route('tonneli.index')->with('success', 'پخت تونلی ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش پخت: ' . $e->getMessage()]);
        }
    }

    public function destroy(TonneliFiring $tonneli)
    {
        $tonneli->delete();
        return redirect()->route('tonneli.index')->with('success', 'پخت تونلی حذف شد.');
    }

    private function returnPackagingForItem($item)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $carton->stock += $cartonCount;
                $carton->save();
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $layer->stock += $layerCount;
                $layer->save();
            }
        }
    }

    private function subtractPackagingForItem($item)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $carton->stock -= $cartonCount;
                $carton->save();
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $layer->stock -= $layerCount;
                $layer->save();
            }
        }
    }
}