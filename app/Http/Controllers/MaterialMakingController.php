<?php

namespace App\Http\Controllers;

use App\Models\MaterialMaking;
use App\Models\Formula;
use App\Models\RawMaterial;
use App\Models\InventoryChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class MaterialMakingController extends Controller
{
    public function index(Request $request)
    {
        $source = $request->input('source', 'all');

        $query = MaterialMaking::query();

        if ($source === 'manual') {
            $query->where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            });
        } elseif ($source === 'imported') {
            $query->where('is_imported', true);
        }

        $records = $query
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('day', 'desc')
            ->paginate(50)
            ->appends($request->all());

        $formulas = Formula::orderBy('name')->get();

        return view('material-making.index', compact('records', 'source', 'formulas'));
    }

    public function import()
    {
        return view('material-making.import');
    }

    public function importStore(Request $request)
    {
        return redirect()->route('material-making.index');
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ثبت دستی مواد سازی
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'         => 'required|string',
            'name'         => 'nullable|string|max:255',
            'material'     => 'required|string|max:255',
            'quantity'     => 'required|numeric|min:0.01',
            'mill_weight'  => 'required|numeric|min:0.01',
        ]);

        try {
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $validated['date']);
            $year = $jalaliDate->getYear();
            $month = $jalaliDate->getMonth();
            $day = $jalaliDate->getDay();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ شمسی نادرست است.'])->withInput();
        }

        // تبدیل وزن بالمیل از کیلوگرم به گرم
        $millWeightGram = (float) $validated['mill_weight'] * 1000;

        DB::beginTransaction();
        try {
            MaterialMaking::create([
                'year'        => $year,
                'month'       => $month,
                'day'         => $day,
                'name'        => $validated['name'] ?? null,
                'material'    => $validated['material'],
                'quantity'    => $validated['quantity'],
                'mill_weight' => $millWeightGram,
                'is_imported' => false, // ✅ دستی
            ]);

            // ✅ کسر از مواد اولیه (چون دستی ثبت کردیم)
            $this->subtractMaterialsForFormula(
                $validated['material'],
                $validated['quantity'],
                $millWeightGram
            );

            DB::commit();

            return redirect()->route('material-making.index')
                ->with('success', '✅ مواد سازی با موفقیت ثبت شد و مواد اولیه کسر شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $record = MaterialMaking::findOrFail($id);

            $this->addMaterialsForFormula($record->material, $record->quantity, $record->mill_weight);

            session(['undo_record' => [
                'class' => MaterialMaking::class,
                'multiple' => false,
                'data' => $record->toArray(),
            ]]);

            $record->delete();
            DB::commit();

            return redirect()->route('material-making.index')
                ->with('success', 'رکورد با موفقیت حذف شد. (قابل بازگرداندن)');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('material-making.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    public function destroyGroup($year, $month, $day)
    {
        $records = MaterialMaking::where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->get();

        if ($records->isEmpty()) {
            return redirect()->route('material-making.index')
                ->withErrors(['error' => 'هیچ رکوردی برای این تاریخ یافت نشد.']);
        }

        DB::beginTransaction();
        try {
            foreach ($records as $record) {
                $this->addMaterialsForFormula($record->material, $record->quantity, $record->mill_weight);
            }

            session(['undo_record' => [
                'class' => MaterialMaking::class,
                'multiple' => true,
                'data' => $records->toArray(),
            ]]);

            MaterialMaking::where('year', $year)
                ->where('month', $month)
                ->where('day', $day)
                ->delete();

            DB::commit();

            return redirect()->route('material-making.index')
                ->with('success', $records->count() . ' رکورد با موفقیت حذف شدند. (قابل بازگرداندن)');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('material-making.index')
                ->with('error', 'خطا در حذف گروهی: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف ایمپورتی‌ها (اکسل)
    // ═══════════════════════════════════════════════════════════
    public function clearImported()
    {
        DB::beginTransaction();
        try {
            $records = MaterialMaking::where('is_imported', true)->get();

            // برگرداندن مواد اولیه به انبار
            foreach ($records as $record) {
                $this->addMaterialsForFormula($record->material, $record->quantity, $record->mill_weight);
            }

            $count = $records->count();
            MaterialMaking::where('is_imported', true)->delete();

            DB::commit();

            return redirect()->route('material-making.index')
                ->with('success', "✅ {$count} رکورد مواد سازی ایمپورتی (اکسل) پاک شد و مواد اولیه برگشت داده شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('material-making.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف دستی‌ها
    // ═══════════════════════════════════════════════════════════
    public function clearManual()
    {
        DB::beginTransaction();
        try {
            $records = MaterialMaking::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->get();

            foreach ($records as $record) {
                $this->addMaterialsForFormula($record->material, $record->quantity, $record->mill_weight);
            }

            $count = $records->count();

            MaterialMaking::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->delete();

            DB::commit();

            return redirect()->route('material-making.index')
                ->with('success', "✅ {$count} رکورد مواد سازی دستی پاک شد و مواد اولیه برگشت داده شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('material-making.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ کسر مواد اولیه (برای ثبت دستی) + ثبت لاگ
    // ═══════════════════════════════════════════════════════════
    private function subtractMaterialsForFormula($formulaName, $quantity, $millWeight)
    {
        $millWeightKg = $millWeight / 1000;
        $totalKg = $quantity * $millWeightKg;

        $formula = Formula::where('name', $formulaName)->first();
        if (!$formula) {
            \Log::warning("فرمول '$formulaName' برای کسر مواد پیدا نشد.");
            return;
        }

        foreach ($formula->items as $item) {
            $consumedKg = ($totalKg * $item->percentage) / 100;
            $consumedGram = $consumedKg * 1000;

            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $oldStock = (float) $rawMaterial->stock;
                $newStock = max(0, $oldStock - $consumedGram);

                // ✅ ثبت لاگ
                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $rawMaterial,
                        'stock',
                        $oldStock,
                        $newStock,
                        'adjust',
                        null,
                        'manual_material_making',
                        'مواد سازی دستی - ' . $formulaName
                    );
                }

                $rawMaterial->stock = $newStock;
                $rawMaterial->save();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ برگرداندن مواد اولیه (برای حذف) + ثبت لاگ
    // ═══════════════════════════════════════════════════════════
    private function addMaterialsForFormula($formulaName, $quantity, $millWeight)
    {
        $millWeightKg = $millWeight / 1000;
        $totalKg = $quantity * $millWeightKg;

        $formula = Formula::where('name', $formulaName)->first();
        if (!$formula) {
            \Log::warning("فرمول '$formulaName' برای برگرداندن مواد پیدا نشد.");
            return;
        }

        foreach ($formula->items as $item) {
            $consumedKg = ($totalKg * $item->percentage) / 100;
            $consumedGram = $consumedKg * 1000;

            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $oldStock = (float) $rawMaterial->stock;
                $newStock = $oldStock + $consumedGram;

                // ✅ ثبت لاگ برگشت
                if ($oldStock != $newStock) {
                    InventoryChangeLog::log(
                        $rawMaterial,
                        'stock',
                        $oldStock,
                        $newStock,
                        'adjust',
                        null,
                        'manual_material_making_return',
                        'برگشت مواد سازی دستی - ' . $formulaName
                    );
                }

                $rawMaterial->stock = $newStock;
                $rawMaterial->save();
            }
        }
    }
}