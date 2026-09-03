<?php

namespace App\Http\Controllers;

use App\Models\ShuttleFiring;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class ShuttleFiringController extends Controller
{
    /**
     * نمایش لیست پخت‌ها به‌صورت گروه‌بندی‌شده بر اساس شماره پخت
     * همراه با قابلیت فیلتر بر اساس نوع کوره
     */
    public function index(Request $request)
    {
        // دریافت نوع کوره از پارامتر کوئری (در صورت وجود)
        $filterKiln = $request->query('kiln');

        // دریافت همه رکوردها با محصولات مرتبط
        $query = ShuttleFiring::with('product')
            ->orderBy('date', 'desc')
            ->orderBy('firing_number', 'desc');

        // اعمال فیلتر بر اساس نوع کوره (اگر پارامتر وجود داشته باشد)
        if ($filterKiln && $filterKiln !== 'all') {
            $query->where('kiln_type', $filterKiln);
        }

        $allFirings = $query->get();

        // گروه‌بندی بر اساس کلید کامل (تاریخ + کوره + شماره پخت)
        $grouped = $allFirings->groupBy(function ($item) {
            return $item->year . '-' . $item->month . '-' . $item->day . '-' . $item->kiln_type . '-' . $item->firing_number;
        });

        // ایجاد مجموعه‌ای از گروه‌ها با اطلاعات خلاصه
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
            ];
        })->values();

        // ===== محاسبه تعداد پخت‌های هر کوره (از کل داده‌ها بدون فیلتر) =====
        // ✅ اصلاح شده: استفاده از || به جای concat برای SQLite
        $allKilnCounts = ShuttleFiring::select('kiln_type')
            ->selectRaw("count(distinct (year || '-' || month || '-' || day || '-' || kiln_type || '-' || firing_number)) as count")
            ->groupBy('kiln_type')
            ->pluck('count', 'kiln_type');

        // صفحه‌بندی دستی (بر اساس داده‌های فیلترشده)
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $firings->forPage($currentPage, $perPage),
            $firings->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // ارسال داده‌ها به ویو
        return view('shuttle.index', compact('paginated', 'allKilnCounts', 'filterKiln'));
    }

    /**
     * نمایش فرم ثبت پخت جدید
     */
    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('shuttle.create', compact('products', 'today'));
    }

    /**
     * ذخیره پخت جدید
     */
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

            ShuttleFiring::create([
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
            ]);

            DB::commit();
            return redirect()->route('shuttle.index')
                ->with('success', 'پخت شاتل با موفقیت ثبت شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت پخت: ' . $e->getMessage()]);
        }
    }

    /**
     * نمایش جزئیات یک پخت با کلید کامل
     */
    public function show($year, $month, $day, $kiln_type, $firingNumber)
    {
        $main = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->where('kiln_type', $kiln_type)
            ->where('firing_number', $firingNumber)
            ->first();

        if (!$main) {
            return redirect()->route('shuttle.index')
                ->with('error', 'پخت مورد نظر یافت نشد.');
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

    /**
     * نمایش فرم ویرایش پخت با کلید کامل
     */
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

    /**
     * به‌روزرسانی پخت با کلید کامل
     */
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
            $firing->update([
                'date' => $gregorianDate,
                'kiln_type' => $kilnType,
                'firing_subtype' => $firingSubtype,
                'product_id' => $validated['product_id'],
                'output_quantity' => $validated['main_quantity'],
                'is_packaged' => $packaged,
            ]);

            DB::commit();
            return redirect()->route('shuttle.index')
                ->with('success', 'پخت شاتل با موفقیت ویرایش شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش پخت: ' . $e->getMessage()]);
        }
    }

    /**
     * حذف یک پخت (همه آیتم‌های آن) با کلید کامل
     */
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

    // ============================================================
    //  توابع کمکی
    // ============================================================

    private function mapKilnNumberToType($kilnNumber, $firingType = null)
    {
        $kilnNumber = trim($kilnNumber);
        
        if (is_numeric($kilnNumber)) {
            $num = (int)$kilnNumber;
            if ($num >= 1 && $num <= 4) {
                return 'kiln_' . $num;
            }
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