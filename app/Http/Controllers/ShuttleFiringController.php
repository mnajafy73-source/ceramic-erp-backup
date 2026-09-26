<?php

namespace App\Http\Controllers;

use App\Models\ShuttleFiring;
use App\Models\Product;
use App\Models\WarehouseInventory;
use App\Models\Glaze1300Inventory;
use App\Models\WaxInventory;
use App\Models\InventoryChangeLog;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class ShuttleFiringController extends Controller
{
    public function index(Request $request)
    {
        $filterKiln = $request->query('kiln');
        $source = $request->input('source', 'all');

        $query = ShuttleFiring::with('product')
            ->orderBy('date', 'desc')
            ->orderBy('firing_number', 'desc');

        if ($filterKiln && $filterKiln !== 'all') {
            $query->where('kiln_type', $filterKiln);
        }

        if ($source === 'manual') {
            $query->where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            });
        } elseif ($source === 'imported') {
            $query->where('is_imported', true);
        }

        $allFirings = $query->get();

        $grouped = $allFirings->groupBy(function ($item) {
            return $item->year . '-' . $item->month . '-' . $item->day . '-' . $item->kiln_type . '-' . $item->firing_number;
        });

        $firings = $grouped->map(function ($items, $key) {
            $first = $items->first();
            return (object) [
                'firing_number' => $first->firing_number,
                'date' => $first->jalali_date,
                'kiln_type' => $first->kiln_type,
                'firing_subtype' => $first->firing_subtype,
                'year' => $first->year,
                'month' => $first->month,
                'day' => $first->day,
                'items' => $items,
                'total_quantity' => $items->sum('output_quantity'),
                'products_count' => $items->count(),
                'is_packaged' => $items->contains('is_packaged', true),
                'is_imported' => $items->contains('is_imported', true),
            ];
        })->values();

        $allKilnCounts = ShuttleFiring::select('kiln_type')
            ->selectRaw("count(distinct (year || '-' || month || '-' || day || '-' || kiln_type || '-' || firing_number)) as count")
            ->groupBy('kiln_type')
            ->pluck('count', 'kiln_type');

        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $firings->forPage($currentPage, $perPage),
            $firings->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('shuttle.index', compact('paginated', 'allKilnCounts', 'filterKiln', 'source'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('shuttle.create', compact('products', 'today'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ثبت پخت شاتل — یک لاگ واحد
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'kiln_number' => 'required|in:1,2,3,4,packaging,بسته‌بندی',
            'firing_type' => 'required|in:معمولی,1300,لعابدار,موم',
            'product_id' => 'required|exists:products,id',
            'total_quantity' => 'required|numeric|min:0',
            'main_quantity' => 'required|numeric|min:0',
            'waste' => 'required|numeric|min:0',
            'is_packaged' => 'nullable|boolean',
        ]);

        try {
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $validated['date']);
            $gregorianDate = $jalaliDate->toCarbon();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $yearNum = $jalaliDate->getYear();
        $monthNum = $jalaliDate->getMonth();
        $dayNum = $jalaliDate->getDay();

        $kilnType = $this->mapKilnNumberToType($validated['kiln_number'], $validated['firing_type']);

        if ($kilnType === 'packaging') {
            $packaged = 1;
        } else {
            $packaged = $request->has('is_packaged') ? 1 : 0;
        }

        $firingSubtype = null;
        if ($kilnType === 'kiln_3') {
            if (strpos($validated['firing_type'], 'لعاب') !== false) {
                $firingSubtype = 'glaze';
            } elseif (strpos($validated['firing_type'], 'موم') !== false) {
                $firingSubtype = 'mum';
            }
        }

        DB::beginTransaction();
        try {
            $maxNumber = ShuttleFiring::where('year', $yearNum)
                ->where('month', $monthNum)
                ->where('day', $dayNum)
                ->where('kiln_type', $kilnType)
                ->max('firing_number') ?? 0;

            $newFiringNumber = $maxNumber + 1;

            $product = Product::find($validated['product_id']);

            // ✅ ثبت دستی → is_imported = false
            $firing = ShuttleFiring::create([
                'date' => $gregorianDate,
                'kiln_type' => $kilnType,
                'firing_subtype' => $firingSubtype,
                'product_id' => $validated['product_id'],
                'output_quantity' => $validated['main_quantity'],
                'firing_number' => $newFiringNumber,
                'is_packaged' => $packaged,
                'year' => $yearNum,
                'month' => $monthNum,
                'day' => $dayNum,
                'is_imported' => false,
            ]);

            $this->applyInventoryForFiringWithLog(
                $firing,
                'add',
                $validated['date'],
                $validated['total_quantity'],
                $validated['main_quantity'],
                $validated['waste']
            );

            DB::commit();
            return redirect()->route('shuttle.index')
                ->with('success', 'پخت شاتل با موفقیت ثبت شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت پخت: ' . $e->getMessage()]);
        }
    }

    public function show($year, $month, $day, $kiln_type, $firingNumber)
    {
        $main = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->first();

        if (!$main) {
            return redirect()->route('shuttle.index')->with('error', 'پخت مورد نظر یافت نشد.');
        }

        $items = ShuttleFiring::with('product')
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->get();

        $firing = (object) [
            'firing_number' => $firingNumber,
            'date' => $main->jalali_date,
            'kiln_type' => $kiln_type,
            'firing_subtype' => $main->firing_subtype,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'items' => $items,
            'total_quantity' => $items->sum('output_quantity'),
            'products_count' => $items->count(),
        ];

        return view('shuttle.show', compact('firing'));
    }

    public function edit($year, $month, $day, $kiln_type, $firingNumber)
    {
        $firing = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->firstOrFail();

        $products = Product::where('status', 1)->orderBy('name')->get();
        $firing->jalali_date = Jalalian::fromCarbon($firing->date)->format('Y/m/d');

        $kilnNumber = $this->mapKilnTypeToNumber($firing->kiln_type);
        $firing->kiln_number = $kilnNumber;
        $firing->firing_type = $this->getFiringType($firing);
        $firing->total_quantity = $firing->output_quantity + 0;

        return view('shuttle.edit', compact('firing', 'products'));
    }

    public function update(Request $request, $year, $month, $day, $kiln_type, $firingNumber)
    {
        $firing = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|string',
            'kiln_number' => 'required|in:1,2,3,4,packaging,بسته‌بندی',
            'firing_type' => 'required|in:معمولی,1300,لعابدار,موم',
            'product_id' => 'required|exists:products,id',
            'total_quantity' => 'required|numeric|min:0',
            'main_quantity' => 'required|numeric|min:0',
            'waste' => 'required|numeric|min:0',
            'is_packaged' => 'nullable|boolean',
        ]);

        try {
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $validated['date']);
            $gregorianDate = $jalaliDate->toCarbon();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $kilnType = $this->mapKilnNumberToType($validated['kiln_number'], $validated['firing_type']);

        if ($kilnType === 'packaging') {
            $packaged = 1;
        } else {
            $packaged = $request->has('is_packaged') ? 1 : 0;
        }

        $firingSubtype = null;
        if ($kilnType === 'kiln_3') {
            if (strpos($validated['firing_type'], 'لعاب') !== false) {
                $firingSubtype = 'glaze';
            } elseif (strpos($validated['firing_type'], 'موم') !== false) {
                $firingSubtype = 'mum';
            }
        }

        DB::beginTransaction();
        try {
            $this->applyInventoryForFiringWithLog(
                $firing,
                'return',
                $firing->jalali_date,
                0,
                $firing->output_quantity,
                0
            );

            $firing->update([
                'date' => $gregorianDate,
                'kiln_type' => $kilnType,
                'firing_subtype' => $firingSubtype,
                'product_id' => $validated['product_id'],
                'output_quantity' => $validated['main_quantity'],
                'is_packaged' => $packaged,
            ]);

            $firing->refresh();

            $this->applyInventoryForFiringWithLog(
                $firing,
                'add',
                $validated['date'],
                $validated['total_quantity'],
                $validated['main_quantity'],
                $validated['waste']
            );

            DB::commit();
            return redirect()->route('shuttle.index')
                ->with('success', 'پخت شاتل با موفقیت ویرایش شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش پخت: ' . $e->getMessage()]);
        }
    }

    public function destroy($year, $month, $day, $kiln_type, $firingNumber)
    {
        $firing = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            $this->applyInventoryForFiringWithLog(
                $firing,
                'return',
                $firing->jalali_date,
                0,
                $firing->output_quantity,
                0
            );

            ShuttleFiring::where('year', $year)
                ->where('month', $month)
                ->where('day', $day)
                ->where('kiln_type', $kiln_type)
                ->where('firing_number', $firingNumber)
                ->delete();

            DB::commit();
            return redirect()->route('shuttle.index')
                ->with('success', 'پخت شاتل با موفقیت حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف پخت: ' . $e->getMessage()]);
        }
    }

    public function clearImported()
    {
        DB::beginTransaction();
        try {
            $count = ShuttleFiring::where('is_imported', true)->count();
            ShuttleFiring::where('is_imported', true)->delete();

            DB::commit();

            return redirect()->route('shuttle.index')
                ->with('success', "✅ {$count} پخت شاتل ایمپورتی (اکسل) پاک شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('shuttle.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    public function clearManual()
    {
        DB::beginTransaction();
        try {
            $records = ShuttleFiring::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->get();

            $count = 0;
            foreach ($records as $firing) {
                $this->applyInventoryForFiringWithLog(
                    $firing,
                    'return',
                    $firing->jalali_date,
                    0,
                    $firing->output_quantity,
                    0
                );
                $firing->delete();
                $count++;
            }

            DB::commit();

            return redirect()->route('shuttle.index')
                ->with('success', "✅ {$count} پخت شاتل دستی پاک شد و موجودی اصلاح شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('shuttle.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    private function applyInventoryForFiringWithLog(
        ShuttleFiring $firing,
        $action,
        $jalaliDate,
        $totalQty,
        $mainQty,
        $waste
    ) {
        $product = $firing->product;
        if (!$product) return;

        $qty = (float) $firing->output_quantity;
        $kilnType = $firing->kiln_type;
        $isPackaged = (int) $firing->is_packaged;
        $sign = ($action === 'add') ? 1 : -1;

        $changes = [];

        if ($kilnType === 'kiln_1') {
            if ($isPackaged && $qty > 0) {
                $this->collectStock($changes, WarehouseInventory::class, $product->id, $qty * $sign, $product->name, 'انبار');
            }
        }
        elseif ($kilnType === 'kiln_2') {
            if ($qty > 0) {
                $this->collectStock($changes, Glaze1300Inventory::class, $product->id, $qty * $sign, $product->name, '۱۳۰۰');
                if ($isPackaged) {
                    $this->collectStock($changes, Glaze1300Inventory::class, $product->id, -$qty * $sign, $product->name, '۱۳۰۰ (خروج برای بسته‌بندی)');
                    $this->collectStock($changes, WarehouseInventory::class, $product->id, $qty * $sign, $product->name, 'انبار');
                }
            }
        }
        elseif ($kilnType === 'kiln_3') {
            if ($qty > 0 && $firing->firing_subtype === 'mum') {
                $this->collectStock($changes, WaxInventory::class, $product->id, $qty * $sign, $product->name, 'موم');
            }
        }
        elseif ($kilnType === 'kiln_4') {
            if ($isPackaged && $qty > 0) {
                $this->collectStock($changes, Glaze1300Inventory::class, $product->id, -$qty * $sign, $product->name, '۱۳۰۰ (خروج برای بسته‌بندی)');
                $this->collectStock($changes, WarehouseInventory::class, $product->id, $qty * $sign, $product->name, 'انبار');
            }
        }
        elseif ($kilnType === 'packaging') {
            if ($qty > 0) {
                $this->collectStock($changes, WarehouseInventory::class, $product->id, $qty * $sign, $product->name, 'انبار');
            }
        }

        if (!empty($changes)) {
            $title = ($action === 'add') ? 'ثبت پخت شاتل' : 'برگشت پخت شاتل';
            $details = $this->buildShuttleDetails(
                $title,
                $jalaliDate,
                $firing,
                $product,
                $totalQty,
                $mainQty,
                $waste,
                $changes,
                $action
            );
            InventoryChangeLog::logEvent(
                'App\Models\WarehouseInventory',
                $product->id,
                ($action === 'add' ? 'shuttle' : 'shuttle_return'),
                $title,
                $details
            );
        }
    }

    private function collectStock(&$changes, $modelClass, $productId, $delta, $productName, $inventoryType)
    {
        if (abs($delta) < 0.001) return;

        $inv = $modelClass::firstOrCreate(['product_id' => $productId]);
        $old = (float) $inv->stock;
        $new = max(0, $old + $delta);

        if ($old == $new) return;

        $inv->stock = $new;
        $inv->save();

        $changes[] = [
            'inventory' => $inventoryType,
            'product'   => $productName,
            'old'       => $old,
            'new'       => $new,
        ];
    }

    private function buildShuttleDetails($title, $jalaliDate, $firing, $product, $totalQty, $mainQty, $waste, array $changes, $action)
    {
        $lines = [];
        $lines[] = '📋 ' . $title;
        $lines[] = "🔹 تاریخ: {$jalaliDate}";

        $kilnDisplay = $this->getKilnDisplayName($firing->kiln_type);
        $firingTypeDisplay = $this->getFiringType($firing);
        $packagedText = $firing->is_packaged ? ' — بسته‌بندی‌شده' : '';
        $lines[] = "🔹 کوره «{$kilnDisplay}» — نوع پخت: {$firingTypeDisplay}{$packagedText}";
        $lines[] = "🔹 محصول «{$product->name}» — تعداد اصلی: " . number_format($mainQty) . " عدد";
        if ($totalQty > 0) $lines[] = "🔹 تعداد کل: " . number_format($totalQty) . " عدد";
        if ($waste > 0) $lines[] = "🔹 ضایعات: " . number_format($waste) . " عدد";

        $lines[] = '📦 تغییرات موجودی به شرح زیر اعمال شد:';
        $lines[] = '📌 موجودی‌ها:';

        foreach ($changes as $c) {
            $delta = $c['new'] - $c['old'];
            $lines[] = sprintf(
                'CHANGE_RAW|%s (%s)|%d|%d|%d',
                $c['product'],
                $c['inventory'],
                (int) round($c['old']),
                (int) round($c['new']),
                (int) round($delta)
            );
        }

        return implode("\n", $lines);
    }

    private function getKilnDisplayName($kilnType)
    {
        if ($kilnType === 'packaging') return 'بسته‌بندی';
        if (str_starts_with($kilnType, 'kiln_')) return 'کوره ' . substr($kilnType, 5);
        return 'نامشخص';
    }

    private function mapKilnNumberToType($kilnNumber, $firingType = null)
    {
        $kilnNumber = trim($kilnNumber);

        if (is_numeric($kilnNumber)) {
            $num = (int)$kilnNumber;
            if ($num >= 1 && $num <= 4) return 'kiln_' . $num;
        }

        if (strtolower($kilnNumber) === 'بسته‌بندی' || strtolower($kilnNumber) === 'packaging') {
            return 'packaging';
        }

        if ($firingType) {
            if (strpos($firingType, 'معمولی') !== false) return 'kiln_1';
            if (strpos($firingType, '1300') !== false) return 'kiln_2';
            if (strpos($firingType, 'لعاب') !== false || strpos($firingType, 'موم') !== false) return 'kiln_3';
        }

        return 'kiln_1';
    }

    private function mapKilnTypeToNumber($kilnType)
    {
        if ($kilnType === 'packaging') return 'بسته‌بندی';
        $num = str_replace('kiln_', '', $kilnType);
        return is_numeric($num) ? $num : '1';
    }

    private function getFiringType($firing)
    {
        if ($firing->kiln_type === 'kiln_1') return 'معمولی';
        if ($firing->kiln_type === 'kiln_2') return '1300';
        if ($firing->kiln_type === 'kiln_3') {
            if ($firing->firing_subtype === 'glaze') return 'لعابدار';
            if ($firing->firing_subtype === 'mum') return 'موم';
        }
        return 'معمولی';
    }
}