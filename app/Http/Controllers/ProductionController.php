<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // ✅ اضافه شد

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $productions = Production::select('date', DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->appends($request->all());

        $allProducts = Product::where('status', true)->orderBy('name')->get();

        return view('productions.index', compact('productions', 'allProducts'));
    }

    public function showByDate(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            return redirect()->route('productions.index');
        }

        // ✅ تبدیل رشته به شیء Carbon
        $carbonDate = Carbon::parse($date);

        $productions = Production::with(['operator', 'press', 'product', 'stops'])
            ->whereDate('date', $carbonDate)
            ->orderBy('id', 'desc')
            ->get();

        $jalaliDate = Jalalian::fromCarbon($carbonDate)->format('Y/m/d');

        return view('productions.show_by_date', compact('productions', 'jalaliDate', 'date'));
    }

    public function create()
    {
        $operators = Operator::where('status', true)->get();
        $presses   = Press::where('status', true)->get();
        $products  = Product::where('status', true)->where('in_production', true)->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');

        return view('productions.create', compact('operators', 'presses', 'products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date' => 'required|string',
            'rows' => 'required|array|min:1',
            'rows.*.operator_id' => 'required|exists:operators,id',
            'rows.*.product_id' => 'required|exists:products,id',
            'rows.*.stage' => 'nullable|in:production,payment,packaging',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.time_hours' => 'nullable|numeric|min:0',
            'rows.*.stop_types' => 'nullable|array',
            'rows.*.stop_types.*' => 'in:machine_failure,mold_change_repair',
            'rows.*.stop_hours' => 'nullable|array',
            'rows.*.stop_hours.*' => 'numeric|min:0',
        ]);

        $validator->sometimes('rows.*.press_id', 'required|exists:presses,id', function ($input, $item) {
            return isset($item['stage']) && $item['stage'] === 'production';
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        try {
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ نادرست است.'])->withInput();
        }

        DB::beginTransaction();

        try {
            foreach ($validated['rows'] as $row) {
                $data = [
                    'date' => $gregorianDate,
                    'operator_id' => $row['operator_id'],
                    'product_id' => $row['product_id'],
                    'stage' => $row['stage'] ?? null,
                    'quantity' => $row['quantity'],
                    'time_hours' => $row['time_hours'] ?? null,
                    'press_id' => null,
                ];

                if (($row['stage'] ?? '') === 'production') {
                    $data['press_id'] = $row['press_id'];
                }

                $production = Production::create($data);

                if (!empty($row['stop_types']) && !empty($row['stop_hours'])) {
                    foreach ($row['stop_types'] as $index => $type) {
                        if (!empty($type) && isset($row['stop_hours'][$index]) && $row['stop_hours'][$index] > 0) {
                            $production->stops()->create([
                                'type' => $type,
                                'hours' => $row['stop_hours'][$index],
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('productions.index')->with('success', 'تولید با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت تولید: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Production $production)
    {
        $production->load(['operator', 'press', 'product', 'stops']);
        return view('productions.show', compact('production'));
    }

    public function edit(Production $production)
    {
        $operators = Operator::where('status', true)->get();
        $presses   = Press::where('status', true)->get();
        $products  = Product::where('status', true)->where('in_production', true)->get();
        $production->jalali_date = Jalalian::fromCarbon($production->date)->format('Y/m/d');

        return view('productions.edit', compact('production', 'operators', 'presses', 'products'));
    }

    public function update(Request $request, Production $production)
    {
        $rules = [
            'date'        => 'required|string',
            'operator_id' => 'required|exists:operators,id',
            'product_id'  => 'required|exists:products,id',
            'stage'       => 'required|in:production,payment,packaging',
            'quantity'    => 'required|numeric|min:0',
            'time_hours'  => 'nullable|numeric|min:0',
            'stop_types'  => 'nullable|array',
            'stop_types.*'=> 'in:machine_failure,mold_change_repair',
            'stop_hours'  => 'nullable|array',
            'stop_hours.*'=> 'numeric|min:0',
        ];

        if ($request->input('stage') === 'production') {
            $rules['press_id'] = 'required|exists:presses,id';
        } else {
            $rules['press_id'] = 'nullable|exists:presses,id';
        }

        $validated = $request->validate($rules);

        try {
            $validated['date'] = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ نادرست است.'])->withInput();
        }

        if ($validated['stage'] !== 'production') {
            $validated['press_id'] = null;
        }

        $production->update($validated);
        $production->stops()->delete();

        if ($request->has('stop_types') && $request->has('stop_hours')) {
            foreach ($request->stop_types as $index => $type) {
                if (!empty($type) && isset($request->stop_hours[$index]) && $request->stop_hours[$index] > 0) {
                    $production->stops()->create([
                        'type'  => $type,
                        'hours' => $request->stop_hours[$index],
                    ]);
                }
            }
        }

        return redirect()->route('productions.index')
            ->with('success', 'تولید با موفقیت ویرایش شد.');
    }

    public function destroy(Production $production)
    {
        $production->delete();
        return redirect()->route('productions.index')
            ->with('success', 'تولید حذف شد.');
    }
}