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
    public function index(Request $request)
    {
        $source = $request->input('source', 'all');

        $query = TonneliFiring::with('items.product')->latest('date');

        if ($source === 'manual') {
            $query->where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            });
        } elseif ($source === 'imported') {
            $query->where('is_imported', true);
        }

        $firings = $query->paginate(15)->appends($request->all());

        return view('tonneli.index', compact('firings', 'source'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('tonneli.create', compact('products', 'yesterday'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ثبت پخت تونلی — یک لاگ واحد
    // ═══════════════════════════════════════════════════════════
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
            // ✅ ثبت دستی → is_imported = false
            $firing = TonneliFiring::create([
                'date' => $gregorianDate,
                'is_imported' => false,
            ]);

            $changes = [
                'raw'       => [],
                'warehouse' => [],
                'packaging' => [],
            ];

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

                if ($inputQty > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $rawInv->stock;
                    $new = max(0, $old - $inputQty);
                    $rawInv->stock = $new;
                    $rawInv->save();

                    if ($old != $new) {
                        $changes['raw'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }
                }

                if ($outputQty > 0 && $isPackaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $whInv->stock;
                    $new = $old + $outputQty;
                    $whInv->stock = $new;
                    $whInv->save();

                    if ($old != $new) {
                        $changes['warehouse'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }

                    $this->collectPackagingChanges($newItem, $changes);
                }
            }

            $hasAny = !empty($changes['raw']) || !empty($changes['warehouse']) || !empty($changes['packaging']);
            if ($hasAny) {
                $details = $this->buildTonneliDetails(
                    $validated['date'],
                    $firing->fresh('items.product'),
                    $changes,
                    'ثبت پخت تونلی'
                );
                InventoryChangeLog::logEvent(
                    'App\Models\RawInventory',
                    $firing->id,
                    'tonneli_input',
                    'ثبت پخت تونلی',
                    $details
                );
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
            $allChanges = [
                'raw'       => [],
                'warehouse' => [],
                'packaging' => [],
            ];

            // برگرداندن تغییرات قبلی
            $oldItems = $tonneli->items()->with('product')->get();
            foreach ($oldItems as $oldItem) {
                $product = $oldItem->product;
                if (!$product) continue;

                if ($oldItem->input_quantity > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $rawInv->stock;
                    $new = $old + $oldItem->input_quantity;
                    $rawInv->stock = $new;
                    $rawInv->save();

                    if ($old != $new) {
                        $allChanges['raw'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }
                }

                if ($oldItem->output_quantity > 0 && $oldItem->is_packaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $whInv->stock;
                    $new = max(0, $old - $oldItem->output_quantity);
                    $whInv->stock = $new;
                    $whInv->save();

                    if ($old != $new) {
                        $allChanges['warehouse'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }

                    $this->collectPackagingReturn($oldItem, $allChanges);
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

                if ($inputQty > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $rawInv->stock;
                    $new = max(0, $old - $inputQty);
                    $rawInv->stock = $new;
                    $rawInv->save();

                    if ($old != $new) {
                        $allChanges['raw'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }
                }

                if ($outputQty > 0 && $isPackaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $whInv->stock;
                    $new = $old + $outputQty;
                    $whInv->stock = $new;
                    $whInv->save();

                    if ($old != $new) {
                        $allChanges['warehouse'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }

                    $this->collectPackagingChanges($newItem, $allChanges);
                }
            }

            $hasAny = !empty($allChanges['raw']) || !empty($allChanges['warehouse']) || !empty($allChanges['packaging']);
            if ($hasAny) {
                $details = $this->buildTonneliDetails(
                    $validated['date'],
                    $tonneli->fresh('items.product'),
                    $allChanges,
                    'ویرایش پخت تونلی'
                );
                InventoryChangeLog::logEvent(
                    'App\Models\RawInventory',
                    $tonneli->id,
                    'tonneli_input',
                    'ویرایش پخت تونلی',
                    $details
                );
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
            $changes = [
                'raw'       => [],
                'warehouse' => [],
                'packaging' => [],
            ];

            foreach ($items as $item) {
                $product = $item->product;
                if (!$product) continue;

                if ($item->input_quantity > 0) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $rawInv->stock;
                    $new = $old + $item->input_quantity;
                    $rawInv->stock = $new;
                    $rawInv->save();

                    if ($old != $new) {
                        $changes['raw'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }
                }

                if ($item->output_quantity > 0 && $item->is_packaged) {
                    $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $whInv->stock;
                    $new = max(0, $old - $item->output_quantity);
                    $whInv->stock = $new;
                    $whInv->save();

                    if ($old != $new) {
                        $changes['warehouse'][] = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }

                    $this->collectPackagingReturn($item, $changes);
                }
            }

            $hasAny = !empty($changes['raw']) || !empty($changes['warehouse']) || !empty($changes['packaging']);
            if ($hasAny) {
                $details = $this->buildTonneliDetails(
                    $tonneli->jalali_date,
                    $tonneli,
                    $changes,
                    'حذف پخت تونلی'
                );
                InventoryChangeLog::logEvent(
                    'App\Models\RawInventory',
                    $tonneli->id,
                    'tonneli_input_return',
                    'حذف پخت تونلی',
                    $details
                );
            }

            $tonneli->items()->delete();
            $tonneli->delete();
            DB::commit();

            return redirect()->route('tonneli.index')->with('success', 'پخت تونلی حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف: ' . $e->getMessage()]);
        }
    }

    public function clearImported()
    {
        DB::beginTransaction();
        try {
            $records = TonneliFiring::where('is_imported', true)->get();
            $count = 0;

            foreach ($records as $firing) {
                $firing->items()->delete();
                $firing->delete();
                $count++;
            }

            DB::commit();

            return redirect()->route('tonneli.index')
                ->with('success', "✅ {$count} پخت تونلی ایمپورتی (اکسل) پاک شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('tonneli.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    public function clearManual()
    {
        DB::beginTransaction();
        try {
            $records = TonneliFiring::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->get();

            $count = 0;
            foreach ($records as $firing) {
                $items = $firing->items()->with('product')->get();

                foreach ($items as $item) {
                    $product = $item->product;
                    if (!$product) continue;

                    if ($item->input_quantity > 0) {
                        $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                        $old = (float) $rawInv->stock;
                        $new = $old + $item->input_quantity;
                        $rawInv->stock = $new;
                        $rawInv->save();

                        if ($old != $new) {
                            InventoryChangeLog::log(
                                $rawInv, 'stock', $old, $new,
                                'adjust', $product->id,
                                'tonneli_input_return',
                                "برگشت ورودی تونلی (حذف دستی) - {$product->name}"
                            );
                        }
                    }

                    if ($item->output_quantity > 0 && $item->is_packaged) {
                        $whInv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                        $old = (float) $whInv->stock;
                        $new = max(0, $old - $item->output_quantity);
                        $whInv->stock = $new;
                        $whInv->save();

                        if ($old != $new) {
                            InventoryChangeLog::log(
                                $whInv, 'stock', $old, $new,
                                'adjust', $product->id,
                                'tonneli_packaged_return',
                                "برگشت بسته‌بندی تونلی (حذف دستی) - {$product->name}"
                            );
                        }
                    }

                    if ($item->is_packaged && $item->output_quantity > 0) {
                        $this->returnPackagingForItem($item);
                    }
                }

                $firing->items()->delete();
                $firing->delete();
                $count++;
            }

            DB::commit();

            return redirect()->route('tonneli.index')
                ->with('success', "✅ {$count} پخت تونلی دستی پاک شد و موجودی اصلاح شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('tonneli.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    private function collectPackagingChanges($item, array &$changes)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0 || !$product) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $old = (int) $carton->stock;
                $carton->stock = max(0, $old - $cartonCount);
                $carton->save();
                $new = (int) $carton->stock;

                if ($old != $new) {
                    $changes['packaging'][] = [
                        'name' => '[کارتن] ' . $carton->name,
                        'old'  => $old,
                        'new'  => $new,
                    ];
                }
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $old = (int) $layer->stock;
                $layer->stock = max(0, $old - $layerCount);
                $layer->save();
                $new = (int) $layer->stock;

                if ($old != $new) {
                    $changes['packaging'][] = [
                        'name' => '[لایه] ' . $layer->name,
                        'old'  => $old,
                        'new'  => $new,
                    ];
                }
            }
        }
    }

    private function collectPackagingReturn($item, array &$changes)
    {
        $product = $item->product;
        $quantity = $item->output_quantity;
        if ($quantity <= 0 || !$product) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $old = (int) $carton->stock;
                $carton->stock = $old + $cartonCount;
                $carton->save();
                $new = (int) $carton->stock;

                if ($old != $new) {
                    $changes['packaging'][] = [
                        'name' => '[کارتن] ' . $carton->name,
                        'old'  => $old,
                        'new'  => $new,
                    ];
                }
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $old = (int) $layer->stock;
                $layer->stock = $old + $layerCount;
                $layer->save();
                $new = (int) $layer->stock;

                if ($old != $new) {
                    $changes['packaging'][] = [
                        'name' => '[لایه] ' . $layer->name,
                        'old'  => $old,
                        'new'  => $new,
                    ];
                }
            }
        }
    }

    private function buildTonneliDetails($jalaliDate, $firing, array $changes, $actionTitle)
    {
        $lines = [];

        $lines[] = '📋 ' . $actionTitle;
        $lines[] = "🔹 تاریخ: {$jalaliDate}";

        $productTexts = [];
        foreach ($firing->items as $item) {
            $p = $item->product;
            if (!$p) continue;
            $label = "«{$p->name}»";
            $parts = [];
            if ($item->input_quantity > 0) $parts[] = 'ورودی: ' . number_format($item->input_quantity);
            if ($item->output_quantity > 0) $parts[] = 'خروجی: ' . number_format($item->output_quantity);
            if ($item->is_packaged) $parts[] = 'بسته‌بندی‌شده';
            if (!empty($parts)) $label .= ' (' . implode('، ', $parts) . ')';
            $productTexts[] = $label;
        }
        if (!empty($productTexts)) {
            $lines[] = '🔹 محصولات: ' . implode(' — ', $productTexts);
        }

        $lines[] = '📦 تغییرات موجودی به شرح زیر اعمال شد:';

        if (!empty($changes['raw'])) {
            $lines[] = '📌 موجودی خام:';
            foreach ($changes['raw'] as $c) {
                $delta = $c['new'] - $c['old'];
                $lines[] = sprintf(
                    'CHANGE_RAW|%s|%d|%d|%d',
                    $c['name'],
                    (int) round($c['old']),
                    (int) round($c['new']),
                    (int) round($delta)
                );
            }
        }

        if (!empty($changes['warehouse'])) {
            $lines[] = '📌 موجودی انبار:';
            foreach ($changes['warehouse'] as $c) {
                $delta = $c['new'] - $c['old'];
                $lines[] = sprintf(
                    'CHANGE_RAW|%s|%d|%d|%d',
                    $c['name'],
                    (int) round($c['old']),
                    (int) round($c['new']),
                    (int) round($delta)
                );
            }
        }

        if (!empty($changes['packaging'])) {
            $lines[] = '📌 کارتن و لایه:';
            foreach ($changes['packaging'] as $c) {
                $delta = $c['new'] - $c['old'];
                $lines[] = sprintf(
                    'CHANGE_PKG|%s|%d|%d|%d',
                    $c['name'],
                    (int) round($c['old']),
                    (int) round($c['new']),
                    (int) round($delta)
                );
            }
        }

        return implode("\n", $lines);
    }

    private function returnPackagingForItem($item)
    {
        $changes = ['packaging' => []];
        $this->collectPackagingReturn($item, $changes);
    }

    private function subtractPackagingForItem($item)
    {
        $changes = ['packaging' => []];
        $this->collectPackagingChanges($item, $changes);
    }
}