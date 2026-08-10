<?php

namespace App\Http\Controllers;

use App\Models\ShuttleFiring;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShuttleFiringController extends Controller
{
    /**
     * بررسی وجود جدول shuttle_firings
     */
    private function checkTableExists()
    {
        if (!Schema::hasTable('shuttle_firings')) {
            return false;
        }
        return true;
    }

    public function index(Request $request)
    {
        // اگر جدول وجود ندارد، صفحه خالی با پیام برگردان
        if (!$this->checkTableExists()) {
            return view('shuttle.index', [
                'batches' => collect(),
                'defaultYear' => null,
                'defaultMonth' => null,
                'defaultKiln' => null,
                'summaryData' => [],
                'availableYears' => [],
                'kilnLabels' => []
            ])->with('error', 'بخش شاتل در حال حاضر فعال نیست. لطفاً ابتدا Migration‌های مربوطه را اجرا کنید.');
        }

        $currentJalali = Jalalian::now();
        $defaultYear = $request->input('year', $currentJalali->getYear());
        $defaultMonth = $request->input('month', $currentJalali->getMonth());
        $defaultKiln = $request->input('kiln_type', '');

        $query = ShuttleFiring::query();

        if (!empty($defaultYear) && !empty($defaultMonth)) {
            try {
                $monthPadded = str_pad($defaultMonth, 2, '0', STR_PAD_LEFT);
                $dateString = $defaultYear . '/' . $monthPadded . '/01';
                $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateString);
                $startDate = $jalaliDate->toCarbon()->startOfMonth()->format('Y-m-d');
                $endDate = $jalaliDate->toCarbon()->endOfMonth()->format('Y-m-d');
                $query->whereBetween('date', [$startDate, $endDate]);
            } catch (\Exception $e) {
                // ignore
            }
        }

        if ($request->filled('kiln_type')) {
            $query->where('kiln_type', $defaultKiln);
        }

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

        $batches = $query->select('kiln_type', 'firing_number', 'date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->appends($request->all());

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
            $availableYears = [$defaultYear];
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
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $products = Product::where('status', true)->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('shuttle.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'                  => 'required|array|min:1',
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

        $maxNumber = ShuttleFiring::where('kiln_type', $validated['kiln_type'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->max('firing_number');

        $nextNumber = $maxNumber ? intval($maxNumber) + 1 : 1;

        foreach ($validated['products'] as $product) {
            ShuttleFiring::create([
                'date'            => $gregorianDate,
                'kiln_type'       => $validated['kiln_type'],
                'firing_subtype'  => $validated['firing_subtype'] ?? null,
                'product_id'      => $product['product_id'],
                'output_quantity' => $product['output_quantity'] ?? null,
                'firing_number'   => $nextNumber,
                'is_packaged'     => !empty($product['is_packaged']),
                'year'            => $year,
                'month'           => $month,
                'day'             => $day,
            ]);
        }

        return redirect()->route('shuttle.create')->with('success', "پخت شماره {$nextNumber} با موفقیت ثبت شد.");
    }

    public function show($firingNumber, Request $request)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $date = $request->query('date');
        $kilnType = $request->query('kiln_type');
        $items = ShuttleFiring::with('product')
            ->where('firing_number', $firingNumber)
            ->whereDate('date', $date)
            ->where('kiln_type', $kilnType)
            ->get();

        if ($items->isEmpty()) abort(404);
        return view('shuttle.show', compact('items', 'firingNumber', 'date', 'kilnType'));
    }

    public function edit($firingNumber, Request $request)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $date = $request->query('date');
        $kilnType = $request->query('kiln_type');
        $items = ShuttleFiring::with('product')
            ->where('firing_number', $firingNumber)
            ->whereDate('date', $date)
            ->where('kiln_type', $kilnType)
            ->get();

        if ($items->isEmpty()) abort(404);

        $products = Product::where('status', true)->get();
        $jalaliDate = $items->first()->jalali_date ?? '';
        return view('shuttle.edit', compact('items', 'firingNumber', 'date', 'kilnType', 'products', 'jalaliDate'));
    }

    public function update($firingNumber, Request $request)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'                  => 'required|array|min:1',
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

        ShuttleFiring::where('firing_number', $firingNumber)
            ->whereDate('date', $request->query('date'))
            ->where('kiln_type', $request->query('kiln_type'))
            ->delete();

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

        $redirectUrl = '/shuttle/batch/' . $firingNumber . '?date=' . $gregorianDate . '&kiln_type=' . $validated['kiln_type'];
        return redirect()->to($redirectUrl)->with('success', "پخت شماره {$firingNumber} ویرایش شد.");
    }

    public function destroy(ShuttleFiring $shuttle)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $shuttle->delete();
        return redirect()->to('/shuttle')->with('success', 'حذف شد.');
    }

    public function destroyBatch(Request $request)
    {
        if (!$this->checkTableExists()) {
            return redirect()->route('shuttle.index')
                ->with('error', 'بخش شاتل در حال حاضر فعال نیست.');
        }

        $validated = $request->validate([
            'firing_number' => 'required|integer',
            'date'          => 'required|date',
            'kiln_type'     => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
        ]);

        ShuttleFiring::where('firing_number', $validated['firing_number'])
            ->whereDate('date', $validated['date'])
            ->where('kiln_type', $validated['kiln_type'])
            ->delete();

        return redirect()->route('shuttle.index')->with('success', 'کل پخت با موفقیت حذف شد.');
    }
}