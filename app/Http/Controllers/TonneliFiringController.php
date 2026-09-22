<?php

namespace App\Http\Controllers;

use App\Models\TonneliFiring;
use App\Models\TonneliFiringItem;
use App\Models\Product;
use App\Models\Packaging;
use App\Models\RawInventory;
use App\Models\WarehouseInventory;
use App\Models\InventoryChangeLog;
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
            $firing = TonneliFiring::create(['date' => $gregorianDate]);

            foreach ($validated['items'] as $item) {
                $inputQty = $item['input_quantity'] ?? 0;
                $outputQty = $item['output_quantity'] ?? 0;
                $isPackaged = isset($item['is_packaged']) && $item['is_packaged'] ? 1 : 0;

                if ($inputQty == 0 && $outputQty == 0) continue;

                $newItem = $firing->items()->create([
                    'product_id' => $item['product_id'],
                    'input_quantity' => $inputQty,
                    'output_quantity' => $outputQty,
                    'is_packaged' => $isPackaged,
                ]);

                $product = $newItem->product;
                if (!$product) continue;

                // ✅ لاگ ورودی تونلی (کسر از موجودی خام)
                if ($inputQty > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = max(0, $oldStock - $inputQty);
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_input',
                            "ورودی کوره تونلی - {$product->name}"
                        );
                    }
                }

                // ✅ لاگ خروجی تونلی (به انبار اضافه می‌شود اگر بسته‌بندی شده)
                if ($outputQty > 0 && $isPackaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $whInv->stock;
                    $newStock = $oldStock + $outputQty;
                    $whInv->stock = $newStock;
                    $whInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $whInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_packaged',
                            "پخت کوره تونلی - بسته‌بندی‌شده - {$product->name}"
                        );
                    }
                }

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
            // ✅ برگرداندن تغییرات قبلی
            $oldItems = $tonneli->items()->with('product')->get();
            foreach ($oldItems as $oldItem) {
                $product = $oldItem->product;
                if (!$product) continue;

                // برگرداندن ورودی به خام
                if ($oldItem->input_quantity > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = $oldStock + $oldItem->input_quantity;
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_input_return',
                            "برگشت ورودی تونلی (ویرایش) - {$product->name}"
                        );
                    }
                }

                // برگرداندن خروجی از انبار
                if ($oldItem->output_quantity > 0 && $oldItem->is_packaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $whInv->stock;
                    $newStock = max(0, $oldStock - $oldItem->output_quantity);
                    $whInv->stock = $newStock;
                    $whInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $whInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_packaged_return',
                            "برگشت بسته‌بندی تونلی (ویرایش) - {$product->name}"
                        );
                    }
                }

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

                if ($inputQty == 0 && $outputQty == 0) continue;

                $newItem = $tonneli->items()->create([
                    'product_id' => $item['product_id'],
                    'input_quantity' => $inputQty,
                    'output_quantity' => $outputQty,
                    'is_packaged' => $isPackaged,
                ]);

                $product = $newItem->product;
                if (!$product) continue;

                // ✅ لاگ ورودی
                if ($inputQty > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = max(0, $oldStock - $inputQty);
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_input',
                            "ورودی کوره تونلی (ویرایش) - {$product->name}"
                        );
                    }
                }

                // ✅ لاگ خروجی
                if ($outputQty > 0 && $isPackaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $whInv->stock;
                    $newStock = $oldStock + $outputQty;
                    $whInv->stock = $newStock;
                    $whInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $whInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_packaged',
                            "پخت کوره تونلی - بسته‌بندی‌شده (ویرایش) - {$product->name}"
                        );
                    }
                }

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
        DB::beginTransaction();
        try {
            $items = $tonneli->items()->with('product')->get();

            foreach ($items as $item) {
                $product = $item->product;
                if (!$product) continue;

                // برگرداندن ورودی
                if ($item->input_quantity > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = $oldStock + $item->input_quantity;
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_input_return',
                            "برگشت ورودی تونلی (حذف) - {$product->name}"
                        );
                    }
                }

                // برگرداندن خروجی
                if ($item->output_quantity > 0 && $item->is_packaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $whInv->stock;
                    $newStock = max(0, $oldStock - $item->output_quantity);
                    $whInv->stock = $newStock;
                    $whInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $whInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'tonneli_packaged_return',
                            "برگشت بسته‌بندی تونلی (حذف) - {$product->name}"
                        );
                    }
                }

                if ($item->is_packaged && $item->output_quantity > 0) {
                    $this->returnPackagingForItem($item);
                }
            }

            $tonneli->delete();
            DB::commit();

            return redirect()->route('tonneli.index')->with('success', 'پخت تونلی حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف: ' . $e->getMessage()]);
        }
    }

    private function returnPackagingForItem($item)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0 || !$product) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $oldStock = (int) $carton->stock;
                $carton->stock += $cartonCount;
                $carton->save();
                $newStock = (int) $carton->stock;

                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $carton, 'stock', $oldStock, $newStock,
                        'adjust', null,
                        'tonneli_packaging_return',
                        "برگشت کارتن (تونلی) - {$carton->name}"
                    );
                }
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $oldStock = (int) $layer->stock;
                $layer->stock += $layerCount;
                $layer->save();
                $newStock = (int) $layer->stock;

                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $layer, 'stock', $oldStock, $newStock,
                        'adjust', null,
                        'tonneli_packaging_return',
                        "برگشت لایه (تونلی) - {$layer->name}"
                    );
                }
            }
        }
    }

    private function subtractPackagingForItem($item)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0 || !$product) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $oldStock = (int) $carton->stock;
                $carton->stock = max(0, $carton->stock - $cartonCount);
                $carton->save();
                $newStock = (int) $carton->stock;

                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $carton, 'stock', $oldStock, $newStock,
                        'adjust', null,
                        'tonneli_packaging_consumed',
                        "مصرف کارتن (پخت تونلی) - {$carton->name}"
                    );
                }
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $oldStock = (int) $layer->stock;
                $layer->stock = max(0, $layer->stock - $layerCount);
                $layer->save();
                $newStock = (int) $layer->stock;

                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $layer, 'stock', $oldStock, $newStock,
                        'adjust', null,
                        'tonneli_packaging_consumed',
                        "مصرف لایه (پخت تونلی) - {$layer->name}"
                    );
                }
            }
        }
    }
}