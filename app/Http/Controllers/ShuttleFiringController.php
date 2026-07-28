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
     * بازنویسی شماره‌های پخت برای یک کوره و ماه مشخص بر اساس ترتیب روزها
     */
    private function renumberFirings($kilnType, $year, $month)
    {
        $days = ShuttleFiring::where('kiln_type', $kilnType)
            ->where('year', $year)
            ->where('month', $month)
            ->select('day')
            ->distinct()
            ->orderBy('day')
            ->pluck('day')
            ->toArray();

        foreach ($days as $index => $day) {
            $number = $index + 1;
            ShuttleFiring::where('kiln_type', $kilnType)
                ->where('year', $year)
                ->where('month', $month)
                ->where('day', $day)
                ->update(['firing_number' => $number]);
        }
    }

    /**
     * محاسبه شماره پخت برای یک روز مشخص
     */
    private function getFiringNumberForDay($kilnType, $year, $month, $day)
    {
        $count = ShuttleFiring::where('kiln_type', $kilnType)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', '<', $day)
            ->distinct('day')
            ->count('day');

        return $count + 1;
    }

    /**
     * بررسی وجود پخت در یک روز خاص (فقط برای همان کوره)
     */
    private function dayHasFiring($kilnType, $year, $month, $day)
    {
        return ShuttleFiring::where('kiln_type', $kilnType)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->exists();
    }

    public function index(Request $request)
    {
        $currentJalali = Jalalian::now();
        
        // پیش‌فرض‌ها (همیشه مقدار دارند)
        $defaultYear = $request->input('year', $currentJalali->getYear());
        $defaultMonth = $request->input('month', $currentJalali->getMonth());
        $defaultKiln = $request->input('kiln_type', 'kiln_1'); // پیش‌فرض کوره ۱

        // ساخت کوئری با فیلترهای اجباری
        $query = ShuttleFiring::query();

        // فیلتر سال و ماه (همیشه وجود دارند)
        try {
            $monthPadded = str_pad($defaultMonth, 2, '0', STR_PAD_LEFT);
            $dateString = $defaultYear . '/' . $monthPadded . '/01';
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateString);
            $startDate = $jalaliDate->toCarbon()->startOfMonth()->format('Y-m-d');
            $endDate = $jalaliDate->toCarbon()->endOfMonth()->format('Y-m-d');
            $query->whereBetween('date', [$startDate, $endDate]);
        } catch (\Exception $e) {
            // اگر خطا رخ داد، از ماه جاری استفاده کن
            $currentYear = $currentJalali->getYear();
            $currentMonth = $currentJalali->getMonth();
            $monthPadded = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
            $dateString = $currentYear . '/' . $monthPadded . '/01';
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateString);
            $startDate = $jalaliDate->toCarbon()->startOfMonth()->format('Y-m-d');
            $endDate = $jalaliDate->toCarbon()->endOfMonth()->format('Y-m-d');
            $query->whereBetween('date', [$startDate, $endDate]);
            $defaultYear = $currentYear;
            $defaultMonth = $currentMonth;
        }

        // فیلتر کوره (همیشه یک مقدار دارد)
        $query->where('kiln_type', $defaultKiln);

        // خلاصه برای جدول بالا (بر اساس فیلترهای اعمال‌شده)
        $summaryQuery = clone $query;
        $summary = $summaryQuery->select('kiln_type', DB::raw('COUNT(DISTINCT firing_number) as total_batches'))
            ->groupBy('kiln_type')
            ->pluck('total_batches', 'kiln_type')
            ->toArray();

        $kilnLabels = ['kiln_1' => 'کوره ۱', 'kiln_2' => 'کوره ۲', 'kiln_3' => 'کوره ۳', 'packaging' => 'بسته‌بندی'];
        $summaryData = [];
        foreach ($kilnLabels as $key => $label) {
            $summaryData[$key] = [
                'label' => $label,
                'count' => $summary[$key] ?? 0,
            ];
        }

        // لیست پخت‌ها با صفحه‌بندی
        $batches = $query->select('kiln_type', 'firing_number', 'date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->appends($request->all());

        // دریافت سال‌های شمسی موجود برای فیلتر
        $allDates = ShuttleFiring::select('date')->distinct()->orderBy('date', 'desc')->get();
        $availableYears = [];
        foreach ($allDates as $item) {
            try {
                $jalali = Jalalian::fromCarbon($item->date);
                $year = $jalali->getYear();
                if (!in_array($year, $availableYears)) {
                    $availableYears[] = $year;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        if (empty($availableYears)) {
            $availableYears = [$currentJalali->getYear()];
        }
        rsort($availableYears);

        return view('shuttle.index', compact(
            'batches',
            'defaultYear',
            'defaultMonth',
            'defaultKiln',
            'summaryData',
            'availableYears',
            'kilnLabels'
        ));
    }

    public function create()
    {
        $products = Product::where('status', true)->whereIn('kiln_type', ['shuttle', 'both'])->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('shuttle.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'        => 'required|array|min:1',
            'products.*.product_id'     => 'required|exists:products,id',
            'products.*.output_quantity'=> 'nullable|numeric|min:0',
            'products.*.is_packaged'    => 'boolean',
        ]);

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $year  = date('Y', strtotime($gregorianDate));
        $month = date('m', strtotime($gregorianDate));
        $day   = date('d', strtotime($gregorianDate));

        if ($this->dayHasFiring($validated['kiln_type'], $year, $month, $day)) {
            return back()->withErrors(['date' => 'برای این روز و این کوره قبلاً یک پخت ثبت شده است.'])->withInput();
        }

        $this->renumberFirings($validated['kiln_type'], $year, $month);
        $firingNumber = $this->getFiringNumberForDay($validated['kiln_type'], $year, $month, $day);

        foreach ($validated['products'] as $product) {
            ShuttleFiring::create([
                'date'            => $gregorianDate,
                'kiln_type'       => $validated['kiln_type'],
                'firing_subtype'  => $validated['firing_subtype'] ?? null,
                'product_id'      => $product['product_id'],
                'output_quantity' => $product['output_quantity'] ?? null,
                'firing_number'   => $firingNumber,
                'is_packaged'     => !empty($product['is_packaged']),
                'year'            => $year,
                'month'           => $month,
                'day'             => $day,
            ]);
        }

        $this->renumberFirings($validated['kiln_type'], $year, $month);

        $actualNumber = ShuttleFiring::where('kiln_type', $validated['kiln_type'])
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->value('firing_number');

        return redirect()->route('shuttle.create')->with('success', "پخت شماره {$actualNumber} با موفقیت ثبت شد.");
    }

    public function show($firingNumber, Request $request)
    {
        $date = $request->query('date');
        $kilnType = $request->query('kiln_type');
        $items = ShuttleFiring::with('product')
            ->where('firing_number', $firingNumber)
            ->whereDate('date', $date)
            ->where('kiln_type', $kilnType)
            ->get();

        if ($items->isEmpty()) {
            abort(404);
        }

        return view('shuttle.show', compact('items', 'firingNumber', 'date', 'kilnType'));
    }

    public function edit($firingNumber, Request $request)
    {
        $date = $request->query('date');
        $kilnType = $request->query('kiln_type');
        $items = ShuttleFiring::with('product')
            ->where('firing_number', $firingNumber)
            ->whereDate('date', $date)
            ->where('kiln_type', $kilnType)
            ->get();

        if ($items->isEmpty()) {
            abort(404);
        }

        $products = Product::where('status', true)->whereIn('kiln_type', ['shuttle', 'both'])->get();
        $jalaliDate = $items->first()->jalali_date ?? '';

        return view('shuttle.edit', compact('items', 'firingNumber', 'date', 'kilnType', 'products', 'jalaliDate'));
    }

    public function update($firingNumber, Request $request)
    {
        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'        => 'required|array|min:1',
            'products.*.product_id'     => 'required|exists:products,id',
            'products.*.output_quantity'=> 'nullable|numeric|min:0',
            'products.*.is_packaged'    => 'boolean',
        ]);

        try {
            $newGregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $newYear  = date('Y', strtotime($newGregorianDate));
        $newMonth = date('m', strtotime($newGregorianDate));
        $newDay   = date('d', strtotime($newGregorianDate));

        $oldDay = date('d', strtotime($request->query('date')));
        if ($oldDay != $newDay || $request->query('kiln_type') != $validated['kiln_type']) {
            if ($this->dayHasFiring($validated['kiln_type'], $newYear, $newMonth, $newDay)) {
                return back()->withErrors(['date' => 'این روز و این کوره قبلاً ثبت شده است.'])->withInput();
            }
        }

        // حذف همه‌ی رکوردهای آن روز و کوره
        ShuttleFiring::whereDate('date', $request->query('date'))
            ->where('kiln_type', $request->query('kiln_type'))
            ->delete();

        $this->renumberFirings($validated['kiln_type'], $newYear, $newMonth);
        $newFiringNumber = $this->getFiringNumberForDay($validated['kiln_type'], $newYear, $newMonth, $newDay);

        foreach ($validated['products'] as $product) {
            ShuttleFiring::create([
                'date'            => $newGregorianDate,
                'kiln_type'       => $validated['kiln_type'],
                'firing_subtype'  => $validated['firing_subtype'] ?? null,
                'product_id'      => $product['product_id'],
                'output_quantity' => $product['output_quantity'] ?? null,
                'firing_number'   => $newFiringNumber,
                'is_packaged'     => !empty($product['is_packaged']),
                'year'            => $newYear,
                'month'           => $newMonth,
                'day'             => $newDay,
            ]);
        }

        $this->renumberFirings($validated['kiln_type'], $newYear, $newMonth);

        $finalNumber = ShuttleFiring::where('kiln_type', $validated['kiln_type'])
            ->where('year', $newYear)
            ->where('month', $newMonth)
            ->where('day', $newDay)
            ->value('firing_number');

        $redirectUrl = '/shuttle/batch/' . $finalNumber . '?date=' . $newGregorianDate . '&kiln_type=' . $validated['kiln_type'];

        return redirect()->to($redirectUrl)->with('success', "پخت شماره {$finalNumber} ویرایش شد.");
    }

    public function destroy(ShuttleFiring $shuttle)
    {
        $kilnType = $shuttle->kiln_type;
        $year = $shuttle->year;
        $month = $shuttle->month;
        $day = $shuttle->day;

        session(['undo_record' => [
            'class' => get_class($shuttle),
            'data'  => $shuttle->toArray(),
        ]]);

        $shuttle->delete();

        $remaining = ShuttleFiring::where('kiln_type', $kilnType)
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->count();

        if ($remaining == 0) {
            $this->renumberFirings($kilnType, $year, $month);
        }

        return redirect()->to('/shuttle')->with('success', 'حذف شد.');
    }

    public function destroyBatch(Request $request)
    {
        $validated = $request->validate([
            'firing_number' => 'required|integer',
            'date'          => 'required|date',
            'kiln_type'     => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
        ]);

        $records = ShuttleFiring::where('firing_number', $validated['firing_number'])
            ->whereDate('date', $validated['date'])
            ->where('kiln_type', $validated['kiln_type'])
            ->get();

        if ($records->isNotEmpty()) {
            session(['undo_record' => [
                'class' => get_class($records->first()),
                'data'  => $records->toArray(),
            ]]);
        }

        ShuttleFiring::where('firing_number', $validated['firing_number'])
            ->whereDate('date', $validated['date'])
            ->where('kiln_type', $validated['kiln_type'])
            ->delete();

        $dateObj = new \DateTime($validated['date']);
        $year = $dateObj->format('Y');
        $month = $dateObj->format('m');

        $this->renumberFirings($validated['kiln_type'], $year, $month);

        return redirect()->route('shuttle.index')->with('success', 'کل پخت با موفقیت حذف شد.');
    }
}