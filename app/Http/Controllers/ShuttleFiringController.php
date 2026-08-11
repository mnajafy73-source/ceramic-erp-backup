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
     * حذف کاما از اعداد ورودی
     */
    protected function cleanNumber($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return str_replace(',', '', $value);
    }

    public function index(Request $request)
    {
        $query = ShuttleFiring::query();

        $batches = $query->select('kiln_type', 'firing_number', 'date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->appends($request->all());

        $summary = ShuttleFiring::select('kiln_type', DB::raw('COUNT(DISTINCT firing_number) as total_batches'))
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

        return view('shuttle.index', compact('batches', 'summaryData', 'kilnLabels'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('shuttle.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $cleanedData = $request->all();
        if (isset($cleanedData['products']) && is_array($cleanedData['products'])) {
            foreach ($cleanedData['products'] as $key => $item) {
                $cleanedData['products'][$key]['output_quantity'] = $this->cleanNumber($item['output_quantity'] ?? 0);
            }
        }
        $request->merge($cleanedData);

        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'        => 'required|array|min:1',
            'products.*.product_id'     => 'required|exists:products,id',
            'products.*.output_quantity'=> 'nullable|numeric|min:0',
            'products.*.is_packaged'    => 'nullable|boolean',
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

        try {
            foreach ($validated['products'] as $product) {
                ShuttleFiring::create([
                    'date'            => $gregorianDate,
                    'kiln_type'       => $validated['kiln_type'],
                    'firing_subtype'  => $validated['firing_subtype'] ?? null,
                    'product_id'      => $product['product_id'],
                    'output_quantity' => $product['output_quantity'] ?? 0,
                    'firing_number'   => $nextNumber,
                    'is_packaged'     => !empty($product['is_packaged']),
                    'year'            => $year,
                    'month'           => $month,
                    'day'             => $day,
                ]);
            }

            return redirect()->route('shuttle.create')->with('success', "پخت شماره {$nextNumber} با موفقیت ثبت شد.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا در ثبت پخت: ' . $e->getMessage()])->withInput();
        }
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

        if ($items->isEmpty()) abort(404);
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

        if ($items->isEmpty()) abort(404);

        $products = Product::where('status', true)->get();
        $jalaliDate = $items->first()->jalali_date ?? '';
        return view('shuttle.edit', compact('items', 'firingNumber', 'date', 'kilnType', 'products', 'jalaliDate'));
    }

    public function update($firingNumber, Request $request)
    {
        $cleanedData = $request->all();
        if (isset($cleanedData['products']) && is_array($cleanedData['products'])) {
            foreach ($cleanedData['products'] as $key => $item) {
                $cleanedData['products'][$key]['output_quantity'] = $this->cleanNumber($item['output_quantity'] ?? 0);
            }
        }
        $request->merge($cleanedData);

        $validated = $request->validate([
            'date'            => 'required|string',
            'kiln_type'       => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype'  => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'products'        => 'required|array|min:1',
            'products.*.product_id'     => 'required|exists:products,id',
            'products.*.output_quantity'=> 'nullable|numeric|min:0',
            'products.*.is_packaged'    => 'nullable|boolean',
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
                'output_quantity' => $product['output_quantity'] ?? 0,
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
        $shuttle->delete();
        return redirect()->to('/shuttle')->with('success', 'حذف شد.');
    }

    public function destroyBatch(Request $request)
    {
        $validated = $request->validate([
            'firing_number' => 'required|integer',
            'date'          => 'required|date',
            'kiln_type'     => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
        ]);

        // پیدا کردن رکوردها
        $records = ShuttleFiring::where('firing_number', $validated['firing_number'])
            ->whereDate('date', $validated['date'])
            ->where('kiln_type', $validated['kiln_type'])
            ->get();

        // حذف هر رکورد به صورت جداگانه تا Observer اجرا شود
        foreach ($records as $record) {
            $record->delete();
        }

        return redirect()->route('shuttle.index')->with('success', 'کل پخت با موفقیت حذف شد.');
    }
}