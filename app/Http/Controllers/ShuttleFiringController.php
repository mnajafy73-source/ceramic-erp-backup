<?php

namespace App\Http\Controllers;

use App\Models\ShuttleFiring;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ShuttleFiringController extends Controller
{
    public function index()
    {
        $batches = ShuttleFiring::select('kiln_type', 'firing_number', 'date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('shuttle.index', compact('batches'));
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

        // استخراج سال و ماه میلادی برای جستجوی دقیق در دیتابیس
        $year  = date('Y', strtotime($gregorianDate));
        $month = date('m', strtotime($gregorianDate));

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
            ]);
        }

        return redirect()->route('shuttle.create')->with('success', "پخت شماره {$nextNumber} با موفقیت ثبت شد.");
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

        ShuttleFiring::where('firing_number', $validated['firing_number'])
            ->whereDate('date', $validated['date'])
            ->where('kiln_type', $validated['kiln_type'])
            ->delete();

        return redirect()->route('shuttle.index')->with('success', 'کل پخت با موفقیت حذف شد.');
    }
}