<?php

namespace App\Http\Controllers;

use App\Models\MaterialMaking;
use App\Models\Formula;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialMakingController extends Controller
{
    public function index()
    {
        $records = MaterialMaking::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('day', 'desc')
            ->paginate(50);
        return view('material-making.index', compact('records'));
    }

    public function import()
    {
        return view('material-making.import');
    }

    public function importStore(Request $request)
    {
        return redirect()->route('material-making.index');
    }

    public function destroy($id)
    {
        $record = MaterialMaking::findOrFail($id);

        $this->addMaterialsForFormula($record->material, $record->quantity, $record->mill_weight);

        session(['undo_record' => [
            'class' => MaterialMaking::class,
            'multiple' => false,
            'data' => $record->toArray(),
        ]]);

        $record->delete();

        return redirect()->route('material-making.index')
            ->with('success', 'رکورد با موفقیت حذف شد. (قابل بازگرداندن)');
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

        return redirect()->route('material-making.index')
            ->with('success', $records->count() . ' رکورد با موفقیت حذف شدند. (قابل بازگرداندن)');
    }

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
                $rawMaterial->stock += $consumedGram;
                $rawMaterial->save();
                \Log::info("برگرداندن مواد: {$rawMaterial->name} + {$consumedGram} گرم (فرمول {$formulaName})");
            }
        }
    }
}