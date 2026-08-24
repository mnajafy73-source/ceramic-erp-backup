<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ProductionController extends Controller
{
    /**
     * نمایش لیست تولیدات گروه‌بندی شده بر اساس تاریخ
     */
    public function index()
    {
        // گروه‌بندی بر اساس تاریخ با جمع‌بندی
        $productions = Production::select(
            'date',
            DB::raw('count(*) as total_rows'),
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('group_concat(distinct operator_id) as operator_ids'),
            DB::raw('group_concat(distinct product_id) as product_ids'),
            DB::raw('group_concat(distinct stage) as stages')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->paginate(50);

        // بارگذاری اطلاعات مرتبط برای هر گروه
        $productions->getCollection()->transform(function ($item) {
            // دریافت نام اپراتورها
            $operatorIds = array_filter(explode(',', $item->operator_ids ?? ''));
            $operators = Operator::whereIn('id', $operatorIds)->pluck('name')->implode('، ');
            
            // دریافت نام محصولات
            $productIds = array_filter(explode(',', $item->product_ids ?? ''));
            $products = Product::whereIn('id', $productIds)->pluck('name')->implode('، ');
            
            // دریافت عملیات‌ها
            $stages = array_filter(explode(',', $item->stages ?? ''));
            
            $item->operators_text = $operators ?: '-';
            $item->products_text = $products ?: '-';
            $item->stages_text = implode('، ', $stages) ?: '-';
            
            return $item;
        });

        return view('productions.index', compact('productions'));
    }

    /**
     * نمایش فرم ثبت تولید
     */
    public function create()
    {
        $operators = Operator::where('status', 1)->orderBy('name')->get();
        $presses = Press::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('productions.create', compact('operators', 'presses', 'products', 'today'));
    }

    /**
     * ذخیره تولید جدید (پشتیبانی از چند ردیف)
     */
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

        // اعتبارسنجی تاریخ شمسی
        try {
            Jalalian::fromFormat('Y/m/d', $request->date);
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ وارد شده معتبر نیست.'])->withInput();
        }

        $count = 0;

        foreach ($request->rows as $rowData) {
            $production = Production::create([
                'date' => $request->date,
                'operator_id' => $rowData['operator_id'],
                'press_id' => $rowData['press_id'] ?? null,
                'product_id' => $rowData['product_id'],
                'stage' => $rowData['stage'],
                'quantity' => $rowData['quantity'],
                'time_hours' => $rowData['time_hours'] ?? 0,
                'notes' => null,
            ]);

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

        return redirect()->route('productions.index')
            ->with('success', $count . ' ردیف تولید با موفقیت ثبت شد.');
    }

    /**
     * نمایش جزئیات یک تولید
     */
    public function show(Production $production)
    {
        $production->load(['operator', 'press', 'product', 'stops']);
        return view('productions.show', compact('production'));
    }

    /**
     * نمایش فرم ویرایش تولید
     */
    public function edit(Production $production)
    {
        $operators = Operator::where('status', 1)->orderBy('name')->get();
        $presses = Press::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $production->load('stops');
        return view('productions.edit', compact('production', 'operators', 'presses', 'products'));
    }

    /**
     * به‌روزرسانی تولید
     */
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

        // اعتبارسنجی تاریخ شمسی
        try {
            Jalalian::fromFormat('Y/m/d', $validated['date']);
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ وارد شده معتبر نیست.'])->withInput();
        }

        $production->update($validated);

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

        return redirect()->route('productions.index')
            ->with('success', 'تولید با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف تولید
     */
    public function destroy(Production $production)
    {
        $production->stops()->delete();
        $production->delete();
        return redirect()->route('productions.index')
            ->with('success', 'تولید با موفقیت حذف شد.');
    }

    /**
     * نمایش تولیدات یک تاریخ خاص (جزئیات)
     */
    public function showByDate(Request $request)
    {
        $date = $request->input('date');
        if (empty($date)) {
            return redirect()->route('productions.index')->withErrors('تاریخ مشخص نشده است.');
        }

        // اعتبارسنجی تاریخ شمسی
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
}