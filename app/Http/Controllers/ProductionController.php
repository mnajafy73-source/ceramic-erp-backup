<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Production::with(['operator', 'press', 'product', 'stops'])->latest('date');

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('operator', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $productions = $query->paginate(15)->appends($request->all());
        $allProducts = Product::where('status', true)->orderBy('name')->get();

        return view('productions.index', compact('productions', 'allProducts'));
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
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ نادرست است.'])->withInput();
        }

        $validated['date'] = $gregorianDate;

        // اگر عملیات "تولید" نبود، پرس را NULL بفرست
        if ($validated['stage'] !== 'production') {
            $validated['press_id'] = null;
        }

        $production = Production::create($validated);

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

        return redirect()->route('productions.create')
            ->with('success', 'تولید با موفقیت ثبت شد.');
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
            $gregorianDate = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ نادرست است.'])->withInput();
        }

        $validated['date'] = $gregorianDate;

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