<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class ProductionController extends Controller
{
    public function index()
    {
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

        $productions->getCollection()->transform(function ($item) {
            $operatorIds = array_filter(explode(',', $item->operator_ids ?? ''));
            $operators = Operator::whereIn('id', $operatorIds)->pluck('name')->implode('، ');
            
            $productIds = array_filter(explode(',', $item->product_ids ?? ''));
            $products = Product::whereIn('id', $productIds)->pluck('name')->implode('، ');
            
            $stages = array_filter(explode(',', $item->stages ?? ''));
            
            $item->operators_text = $operators ?: '-';
            $item->products_text = $products ?: '-';
            $item->stages_text = implode('، ', $stages) ?: '-';
            
            return $item;
        });

        return view('productions.index', compact('productions'));
    }

    public function create()
    {
        $operators = Operator::where('status', 1)->orderBy('name')->get();
        $presses = Press::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('productions.create', compact('operators', 'presses', 'products', 'today'));
    }

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
                ]);

                $this->subtractMaterials($production, $productWeight);

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
            $this->addMaterials($production);

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
            $this->subtractMaterials($production, $newWeight);

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

    public function destroy(Production $production)
    {
        DB::beginTransaction();

        try {
            $this->addMaterials($production);
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
                $this->addMaterials($production);
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

    // ✅ اصلاح شده: متد showByDate با پارامتر $date
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

    // ============================================================
    //  متدهای کمکی کسر و بازگشت مواد اولیه (با لاگ دیباگ)
    // ============================================================

    private function subtractMaterials(Production $production, $weight = null)
    {
        $product = $production->product;
        $weight = $weight ?? $production->product_weight ?? ($product ? $product->weight : null);

        Log::info('===== SUBTRACT MATERIALS =====');
        Log::info('Production ID: ' . $production->id);
        Log::info('Product ID: ' . ($product ? $product->id : 'null'));
        Log::info('Product Name: ' . ($product ? $product->name : 'null'));
        Log::info('Weight: ' . $weight);
        Log::info('Quantity: ' . $production->quantity);
        Log::info('Formula ID: ' . ($product ? $product->formula_id : 'null'));

        if (!$product || !$weight || !$product->formula_id) {
            Log::warning('SKIP: Missing product, weight, or formula_id');
            return;
        }

        $weightInKg = $this->convertWeightToKg($weight);
        $totalMaterialKg = $production->quantity * $weightInKg;

        Log::info('Weight in kg: ' . $weightInKg);
        Log::info('Total material kg: ' . $totalMaterialKg);

        $formulaItems = $product->formula->items;
        Log::info('Formula items count: ' . $formulaItems->count());

        foreach ($formulaItems as $item) {
            $consumedKg = ($totalMaterialKg * $item->percentage) / 100;
            $consumedGram = $consumedKg * 1000;
            Log::info('Raw material ID: ' . $item->raw_material_id . ', Percentage: ' . $item->percentage . '%, Consumed gram: ' . $consumedGram);

            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $oldStock = $rawMaterial->stock;
                $rawMaterial->stock -= $consumedGram;
                $rawMaterial->save();
                Log::info('Raw material "' . $rawMaterial->name . '" stock: ' . $oldStock . ' → ' . $rawMaterial->stock);
            } else {
                Log::error('Raw material not found for ID: ' . $item->raw_material_id);
            }
        }
    }

    private function addMaterials(Production $production)
    {
        $product = $production->product;
        $weight = $production->product_weight ?? ($product ? $product->weight : null);

        if (!$product || !$weight || !$product->formula_id) {
            return;
        }

        $weightInKg = $this->convertWeightToKg($weight);
        $totalMaterialKg = $production->quantity * $weightInKg;

        foreach ($product->formula->items as $item) {
            $consumedKg = ($totalMaterialKg * $item->percentage) / 100;
            $consumedGram = $consumedKg * 1000;
            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $rawMaterial->stock += $consumedGram;
                $rawMaterial->save();
            }
        }
    }

    private function convertWeightToKg($weight)
    {
        return ($weight < 1000) ? $weight / 1000 : $weight;
    }
}