<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use App\Models\RawInventory;
use App\Models\InventoryChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $source = $request->input('source', 'all');

        $query = Production::select(
            'date',
            DB::raw('count(*) as total_rows'),
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('group_concat(distinct operator_id) as operator_ids'),
            DB::raw('group_concat(distinct product_id) as product_ids'),
            DB::raw('group_concat(distinct stage) as stages'),
            DB::raw('group_concat(distinct is_imported) as imported_flags')
        );

        if ($source === 'manual') {
            $query->where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            });
        } elseif ($source === 'imported') {
            $query->where('is_imported', true);
        }

        $productions = $query
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->paginate(50)
            ->appends($request->all());

        $productions->getCollection()->transform(function ($item) {
            $operatorIds = array_filter(explode(',', $item->operator_ids ?? ''));
            $operators = Operator::whereIn('id', $operatorIds)->pluck('name')->implode('، ');

            $productIds = array_filter(explode(',', $item->product_ids ?? ''));
            $products = Product::whereIn('id', $productIds)->pluck('name')->implode('، ');

            $stages = array_filter(explode(',', $item->stages ?? ''));
            $flags = array_filter(explode(',', $item->imported_flags ?? ''));

            $item->operators_text = $operators ?: '-';
            $item->products_text = $products ?: '-';
            $item->stages_text = implode('، ', $stages) ?: '-';

            $hasImported = in_array('1', $flags);
            $hasManual = in_array('0', $flags);

            if ($hasImported && $hasManual) {
                $item->source = 'mixed';
            } elseif ($hasImported) {
                $item->source = 'imported';
            } else {
                $item->source = 'manual';
            }

            return $item;
        });

        $currentSource = $source;

        return view('productions.index', compact('productions', 'currentSource'));
    }

    public function create()
    {
        $operators = Operator::where('status', 1)->orderBy('name')->get();
        $presses = Press::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('productions.create', compact('operators', 'presses', 'products', 'today'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ثبت تولید (دستی)
    //  فقط موجودی خام زیاد می‌شه — نه مواد، نه کارتن، نه لایه
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|string',
            'rows' => 'required|array|min:1',
            'rows.*.operator_id' => 'required|exists:operators,id',
            'rows.*.product_id' => 'required|exists:products,id',
            'rows.*.stage' => 'required|in:تولید,پرداخت,بسته‌بندی',
            'rows.*.press_id' => 'nullable|exists:presses,id',
            'rows.*.quantity' => 'required|numeric|min:0.01',
            'rows.*.time_hours' => 'nullable|numeric|min:0',
            'rows.*.stop_types' => 'nullable|array',
            'rows.*.stop_types.*' => 'in:خرابی ماشین,تعویض قالب',
            'rows.*.stop_hours' => 'nullable|array',
            'rows.*.stop_hours.*' => 'numeric|min:0',
        ]);

        try {
            Jalalian::fromFormat('Y/m/d', $request->date);
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ وارد شده معتبر نیست.'])->withInput();
        }

        $count = 0;
        DB::beginTransaction();

        try {
            foreach ($request->rows as $rowData) {
                $product = Product::find($rowData['product_id']);
                $productWeight = $product ? $product->weight : null;
                $operator = Operator::find($rowData['operator_id']);
                $press = !empty($rowData['press_id']) ? Press::find($rowData['press_id']) : null;

                $production = Production::create([
                    'date' => $request->date,
                    'operator_id' => $rowData['operator_id'],
                    'press_id' => $rowData['press_id'] ?? null,
                    'product_id' => $rowData['product_id'],
                    'product_weight' => $productWeight,
                    'stage' => $rowData['stage'],
                    'quantity' => $rowData['quantity'],
                    'time_hours' => $rowData['time_hours'] ?? 0,
                    'notes' => null,
                    'is_imported' => false,
                ]);

                // ✅ فقط موجودی خام زیاد می‌شه
                $rawChange = null;
                if ($rowData['stage'] === 'تولید' && $product) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $old = (float) $rawInv->stock;
                    $new = $old + (float) $rowData['quantity'];
                    $rawInv->stock = $new;
                    $rawInv->save();

                    if ($old != $new) {
                        $rawChange = [
                            'name' => $product->name,
                            'old'  => $old,
                            'new'  => $new,
                        ];
                    }
                }

                // ✅ لاگ (فقط اگه تغییر داشته)
                if ($rawChange) {
                    $details = $this->buildProductionDetails($rowData, $product, $operator, $press, $rawChange);
                    InventoryChangeLog::logEvent(
                        'App\Models\RawInventory',
                        $product->id,
                        'production',
                        'ثبت تولید',
                        $details
                    );
                }

                // استاپ‌ها
                if (!empty($rowData['stop_types']) && !empty($rowData['stop_hours'])) {
                    foreach ($rowData['stop_types'] as $index => $type) {
                        if (isset($rowData['stop_hours'][$index])) {
                            ProductionStop::create([
                                'production_id' => $production->id,
                                'type' => $type,
                                'hours' => $rowData['stop_hours'][$index],
                            ]);
                        }
                    }
                }

                $count++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }

        return redirect()->route('productions.index')
            ->with('success', $count . ' ردیف تولید با موفقیت ثبت شد.');
    }

    public function show(Production $production)
    {
        $production->load(['operator', 'press', 'product', 'stops']);
        return view('productions.show', compact('production'));
    }

    public function edit(Production $production)
    {
        $operators = Operator::where('status', 1)->orderBy('name')->get();
        $presses = Press::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $production->load('stops');
        return view('productions.edit', compact('production', 'operators', 'presses', 'products'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ویرایش تولید — فقط موجودی خام اصلاح می‌شه
    // ═══════════════════════════════════════════════════════════
    public function update(Request $request, Production $production)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'operator_id' => 'required|exists:operators,id',
            'press_id' => 'nullable|exists:presses,id',
            'product_id' => 'required|exists:products,id',
            'stage' => 'required|in:تولید,پرداخت,بسته‌بندی',
            'quantity' => 'required|numeric|min:0.01',
            'time_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'stop_types' => 'nullable|array',
            'stop_types.*' => 'in:خرابی ماشین,تعویض قالب',
            'stop_hours' => 'nullable|array',
            'stop_hours.*' => 'numeric|min:0',
        ]);

        try {
            Jalalian::fromFormat('Y/m/d', $validated['date']);
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ وارد شده معتبر نیست.'])->withInput();
        }

        DB::beginTransaction();

        try {
            // ۱. برگرداندن موجودی خام قدیمی
            if ($production->stage === 'تولید') {
                $oldProduct = Product::find($production->product_id);
                if ($oldProduct) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $oldProduct->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = max(0, $oldStock - (float) $production->quantity);
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $oldProduct->id,
                            'production_return',
                            "برگشت تولید (ویرایش) - {$oldProduct->name}"
                        );
                    }
                }
            }

            $product = Product::find($validated['product_id']);
            $newWeight = $product ? $product->weight : null;

            $production->update([
                'date' => $validated['date'],
                'operator_id' => $validated['operator_id'],
                'press_id' => $validated['press_id'] ?? null,
                'product_id' => $validated['product_id'],
                'product_weight' => $newWeight,
                'stage' => $validated['stage'],
                'quantity' => $validated['quantity'],
                'time_hours' => $validated['time_hours'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $production->refresh();

            // ۲. افزودن موجودی خام جدید
            if ($validated['stage'] === 'تولید' && $product) {
                $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                $oldStock = (float) $rawInv->stock;
                $newStock = $oldStock + (float) $validated['quantity'];
                $rawInv->stock = $newStock;
                $rawInv->save();

                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $rawInv, 'stock', $oldStock, $newStock,
                        'adjust', $product->id,
                        'production',
                        "ثبت تولید دستی (ویرایش) - {$product->name}"
                    );
                }
            }

            // استاپ‌ها
            $production->stops()->delete();
            if (!empty($request->stop_types) && !empty($request->stop_hours)) {
                foreach ($request->stop_types as $index => $type) {
                    if (isset($request->stop_hours[$index])) {
                        ProductionStop::create([
                            'production_id' => $production->id,
                            'type' => $type,
                            'hours' => $request->stop_hours[$index],
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش: ' . $e->getMessage()]);
        }

        return redirect()->route('productions.index')
            ->with('success', 'تولید با موفقیت به‌روزرسانی شد.');
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف تولید — فقط موجودی خام اصلاح می‌شه
    // ═══════════════════════════════════════════════════════════
    public function destroy(Production $production)
    {
        DB::beginTransaction();

        try {
            if ($production->stage === 'تولید') {
                $product = Product::find($production->product_id);
                if ($product) {
                    $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $rawInv->stock;
                    $newStock = max(0, $oldStock - (float) $production->quantity);
                    $rawInv->stock = $newStock;
                    $rawInv->save();

                    if ($oldStock != $newStock) {
                        InventoryChangeLog::log(
                            $rawInv, 'stock', $oldStock, $newStock,
                            'adjust', $product->id,
                            'production_return',
                            "برگشت تولید (حذف) - {$product->name}"
                        );
                    }
                }
            }

            $production->stops()->delete();
            $production->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف: ' . $e->getMessage()]);
        }

        return redirect()->route('productions.index')
            ->with('success', 'تولید با موفقیت حذف شد.');
    }

    public function destroyGroup($year, $month, $day)
    {
        $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);

        try {
            Jalalian::fromFormat('Y/m/d', $dateStr);
        } catch (\Exception $e) {
            return redirect()->route('productions.index')
                ->withErrors(['error' => 'تاریخ وارد شده معتبر نیست.']);
        }

        $productions = Production::where('date', $dateStr)->get();

        if ($productions->isEmpty()) {
            return redirect()->route('productions.index')
                ->withErrors(['error' => 'هیچ تولیدی برای این تاریخ یافت نشد.']);
        }

        $allItems = [];

        foreach ($productions as $production) {
            $prodData = $production->getAttributes();
            unset($prodData['id'], $prodData['created_at'], $prodData['updated_at']);

            $stopsData = [];
            foreach ($production->stops as $stop) {
                $stopData = $stop->getAttributes();
                unset($stopData['id'], $stopData['production_id'], $stopData['created_at'], $stopData['updated_at']);
                $stopsData[] = $stopData;
            }

            $allItems[] = [
                'production' => $prodData,
                'stops' => $stopsData,
            ];
        }

        session()->put('undo_record', [
            'class' => Production::class,
            'multiple' => true,
            'data' => $allItems,
        ]);

        DB::beginTransaction();

        try {
            foreach ($productions as $production) {
                if ($production->stage === 'تولید') {
                    $product = Product::find($production->product_id);
                    if ($product) {
                        $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                        $oldStock = (float) $rawInv->stock;
                        $newStock = max(0, $oldStock - (float) $production->quantity);
                        $rawInv->stock = $newStock;
                        $rawInv->save();

                        if ($oldStock != $newStock) {
                            InventoryChangeLog::log(
                                $rawInv, 'stock', $oldStock, $newStock,
                                'adjust', $product->id,
                                'production_return',
                                "برگشت تولید (حذف گروهی) - {$product->name}"
                            );
                        }
                    }
                }

                $production->stops()->delete();
                $production->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->forget('undo_record');
            return redirect()->route('productions.index')
                ->withErrors(['error' => 'خطا در حذف گروهی: ' . $e->getMessage()]);
        }

        return redirect()->route('productions.index')
            ->with('success', '✅ ' . $productions->count() . ' رکورد تولید تاریخ ' . $dateStr . ' با موفقیت حذف شدند.');
    }

    public function showByDate($date)
    {
        try {
            Jalalian::fromFormat('Y/m/d', $date);
        } catch (\Exception $e) {
            return redirect()->route('productions.index')->withErrors('تاریخ وارد شده معتبر نیست.');
        }

        $productions = Production::with(['operator', 'press', 'product', 'stops'])
            ->where('date', $date)
            ->orderBy('id', 'desc')
            ->get();

        return view('productions.by-date', compact('productions', 'date'));
    }

    public function clearImported()
    {
        $count = Production::where('is_imported', true)->count();

        if ($count > 0) {
            Production::where('is_imported', true)->delete();
        }

        return redirect()->route('productions.index')
            ->with('success', "✅ {$count} رکورد تولید ایمپورتی (اکسل) پاک شد.");
    }

    public function clearManual()
    {
        DB::beginTransaction();
        try {
            $manualProductions = Production::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->get();

            foreach ($manualProductions as $production) {
                if ($production->stage === 'تولید') {
                    $product = Product::find($production->product_id);
                    if ($product) {
                        $rawInv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                        $oldStock = (float) $rawInv->stock;
                        $newStock = max(0, $oldStock - (float) $production->quantity);
                        $rawInv->stock = $newStock;
                        $rawInv->save();

                        if ($oldStock != $newStock) {
                            InventoryChangeLog::log(
                                $rawInv, 'stock', $oldStock, $newStock,
                                'adjust', $product->id,
                                'production_return',
                                "برگشت تولید دستی (حذف) - {$product->name}"
                            );
                        }
                    }
                }

                $production->stops()->delete();
                $production->delete();
            }

            $count = $manualProductions->count();
            DB::commit();

            return redirect()->route('productions.index')
                ->with('success', "✅ {$count} رکورد تولید دستی پاک شد و موجودی خام اصلاح شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('productions.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ساخت متن جزئیات ثبت تولید (فقط موجودی خام)
    // ═══════════════════════════════════════════════════════════
    private function buildProductionDetails($rowData, $product, $operator, $press, array $rawChange)
    {
        $lines = [];

        $lines[] = '📋 ثبت تولید دستی';

        $pressText = $press ? " — پرس «{$press->name}»" : '';
        $operatorText = $operator ? " — اپراتور «{$operator->name}»" : '';
        $lines[] = sprintf(
            '🔹 محصول «%s» — تعداد %s عدد%s%s',
            $product ? $product->name : '—',
            number_format($rowData['quantity']),
            $pressText,
            $operatorText
        );

        $lines[] = '📦 تغییرات موجودی:';

        $delta = $rawChange['new'] - $rawChange['old'];
        $lines[] = sprintf(
            'CHANGE_RAW|%s|%d|%d|%d',
            $rawChange['name'] . ' (خام)',
            (int) round($rawChange['old']),
            (int) round($rawChange['new']),
            (int) round($delta)
        );

        return implode("\n", $lines);
    }
}