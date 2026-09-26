<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use App\Models\Press;
use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\TonneliFiringItem;
use App\Models\TonneliFiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ReportController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    //  گزارش تولید
    // ═══════════════════════════════════════════════════════════
    public function production(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());
        $month = (int) $request->input('month', $currentJalali->getMonth());

        $startDate = sprintf('%04d/%02d/01', $year, $month);
        $lastDay = Jalalian::fromFormat('Y/m/d', $startDate)->getMonthDays();
        $endDate = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

        // ===== گزارش تولید به تفکیک محصول + پرس =====
        $productions = Production::select(
            'product_id',
            'press_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(time_hours) as total_time_hours')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('product_id', 'press_id')
        ->orderBy('product_id')
        ->orderBy('press_id')
        ->get();

        // ===== توقف‌ها =====
        $stopData = ProductionStop::select(
            'productions.product_id',
            'productions.press_id',
            'production_stops.type',
            DB::raw('SUM(production_stops.hours) as total_hours')
        )
        ->join('productions', 'production_stops.production_id', '=', 'productions.id')
        ->where('productions.date', '>=', $startDate)
        ->where('productions.date', '<=', $endDate)
        ->groupBy('productions.product_id', 'productions.press_id', 'production_stops.type')
        ->get();

        $stopMap = [];
        foreach ($stopData as $stop) {
            $key = $stop->product_id . '-' . $stop->press_id;
            if (!isset($stopMap[$key])) {
                $stopMap[$key] = ['repair' => 0, 'breakdown' => 0];
            }
            if ($stop->type === 'تعویض قالب') {
                $stopMap[$key]['repair'] = $stop->total_hours;
            } elseif ($stop->type === 'خرابی ماشین') {
                $stopMap[$key]['breakdown'] = $stop->total_hours;
            }
        }

        // ===== reportData (جزئیات هر محصول + پرس) =====
        $reportData = [];
        foreach ($productions as $item) {
            $product = Product::find($item->product_id);
            if (!$product) continue;
            $press = Press::find($item->press_id);
            $pressName = $press ? $press->name : 'بدون پرس';

            $key = $item->product_id . '-' . $item->press_id;
            $repair = $stopMap[$key]['repair'] ?? 0;
            $breakdown = $stopMap[$key]['breakdown'] ?? 0;

            $reportData[] = (object) [
                'product_name' => $product->name,
                'press_name' => $pressName,
                'total_quantity' => $item->total_quantity,
                'total_time_hours' => $item->total_time_hours,
                'repair_hours' => $repair,
                'breakdown_hours' => $breakdown,
            ];
        }
        $reportData = collect($reportData);

        // ===== summaryByProduct (خلاصه تجمیعی هر محصول) =====
        $summaryMap = [];
        foreach ($productions as $item) {
            $product = Product::find($item->product_id);
            if (!$product) continue;
            $key = $item->product_id . '-' . $item->press_id;
            $repair = $stopMap[$key]['repair'] ?? 0;
            $breakdown = $stopMap[$key]['breakdown'] ?? 0;

            if (!isset($summaryMap[$item->product_id])) {
                $summaryMap[$item->product_id] = [
                    'product_name' => $product->name,
                    'total_quantity' => 0,
                    'total_time_hours' => 0,
                    'repair_hours' => 0,
                    'breakdown_hours' => 0,
                ];
            }

            $summaryMap[$item->product_id]['total_quantity'] += $item->total_quantity;
            $summaryMap[$item->product_id]['total_time_hours'] += $item->total_time_hours;
            $summaryMap[$item->product_id]['repair_hours'] += $repair;
            $summaryMap[$item->product_id]['breakdown_hours'] += $breakdown;
        }

        $summaryByProduct = collect($summaryMap)->map(function ($item) {
            return (object) $item;
        })->sortBy('product_name')->values();

        return view('reports.production', [
            'reportData' => $reportData,
            'summaryByProduct' => $summaryByProduct,
            'year' => $year,
            'month' => $month,
            'currentYear' => $currentJalali->getYear(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  خروجی CSV گزارش تولید
    // ═══════════════════════════════════════════════════════════
    public function exportProductionCSV(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());
        $month = (int) $request->input('month', $currentJalali->getMonth());

        $startDate = sprintf('%04d/%02d/01', $year, $month);
        $lastDay = Jalalian::fromFormat('Y/m/d', $startDate)->getMonthDays();
        $endDate = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

        $productions = Production::select(
            'product_id',
            'press_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(time_hours) as total_time_hours')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('product_id', 'press_id')
        ->get();

        $stopData = ProductionStop::select(
            'productions.product_id',
            'productions.press_id',
            'production_stops.type',
            DB::raw('SUM(production_stops.hours) as total_hours')
        )
        ->join('productions', 'production_stops.production_id', '=', 'productions.id')
        ->where('productions.date', '>=', $startDate)
        ->where('productions.date', '<=', $endDate)
        ->groupBy('productions.product_id', 'productions.press_id', 'production_stops.type')
        ->get();

        $stopMap = [];
        foreach ($stopData as $stop) {
            $key = $stop->product_id . '-' . $stop->press_id;
            if (!isset($stopMap[$key])) {
                $stopMap[$key] = ['repair' => 0, 'breakdown' => 0];
            }
            if ($stop->type === 'تعویض قالب') {
                $stopMap[$key]['repair'] = $stop->total_hours;
            } elseif ($stop->type === 'خرابی ماشین') {
                $stopMap[$key]['breakdown'] = $stop->total_hours;
            }
        }

        $filename = "گزارش_تولید_{$year}_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($productions, $stopMap) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['نام محصول', 'نام پرس', 'تعداد تولید', 'زمان کارکرد (ساعت)', 'تعویض قالب (ساعت)', 'خرابی ماشین (ساعت)']);

            foreach ($productions as $item) {
                $product = Product::find($item->product_id);
                if (!$product) continue;
                $press = Press::find($item->press_id);
                $pressName = $press ? $press->name : 'بدون پرس';

                $key = $item->product_id . '-' . $item->press_id;
                $repair = $stopMap[$key]['repair'] ?? 0;
                $breakdown = $stopMap[$key]['breakdown'] ?? 0;

                fputcsv($file, [
                    $product->name,
                    $pressName,
                    $item->total_quantity,
                    $item->total_time_hours,
                    $repair,
                    $breakdown,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ═══════════════════════════════════════════════════════════
    //  گزارش پخت
    // ═══════════════════════════════════════════════════════════
    public function firing(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());
        $month = (int) $request->input('month', $currentJalali->getMonth());

        // ===== تونلی (فیلتر بر اساس سال و ماه شمسی) =====
        $tonneliItems = TonneliFiringItem::with('firing')->get();
        $filteredTonneli = $tonneliItems->filter(function ($item) use ($year, $month) {
            if (!$item->firing || !$item->firing->date) return false;
            try {
                $jalali = Jalalian::fromCarbon($item->firing->date);
                return $jalali->getYear() == $year && $jalali->getMonth() == $month;
            } catch (\Exception $e) {
                return false;
            }
        });

        $tonneliGroupQty = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->sum('output_quantity'));

        // ===== شاتل (فیلتر بر اساس year و month) =====
        $shuttleKilns = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->select('product_id', 'kiln_type', 'firing_subtype',
                DB::raw('SUM(output_quantity) as total_qty')
            )
            ->groupBy('product_id', 'kiln_type', 'firing_subtype')
            ->get();

        $shuttleData = [];
        foreach ($shuttleKilns as $row) {
            $productId = $row->product_id;
            $kilnType = $row->kiln_type;
            $key = ($kilnType === 'kiln_3')
                ? (($row->firing_subtype === 'glaze') ? 'kiln_3_glaze' : 'kiln_3_mum')
                : $kilnType;

            if (!isset($shuttleData[$productId])) {
                $shuttleData[$productId] = [
                    'kiln_1' => 0, 'kiln_2' => 0, 'kiln_3_glaze' => 0,
                    'kiln_3_mum' => 0, 'kiln_4' => 0, 'packaging' => 0,
                ];
            }
            if (array_key_exists($key, $shuttleData[$productId])) {
                $shuttleData[$productId][$key] += $row->total_qty;
            }
        }

        // ===== ترکیب =====
        $allProductIds = $tonneliGroupQty->keys()->merge(array_keys($shuttleData))->unique();

        $reportData = [];
        foreach ($allProductIds as $productId) {
            $product = Product::find($productId);
            if (!$product) continue;

            $tonneliQty = $tonneliGroupQty[$productId] ?? 0;

            $kiln1 = $shuttleData[$productId]['kiln_1'] ?? 0;
            $kiln2 = $shuttleData[$productId]['kiln_2'] ?? 0;
            $kiln3Glaze = $shuttleData[$productId]['kiln_3_glaze'] ?? 0;
            $kiln3Mum = $shuttleData[$productId]['kiln_3_mum'] ?? 0;
            $kiln4 = $shuttleData[$productId]['kiln_4'] ?? 0;
            $packaging = $shuttleData[$productId]['packaging'] ?? 0;

            $shuttleTotal = $kiln1 + $kiln2 + $kiln3Glaze + $kiln3Mum + $kiln4 + $packaging;

            $reportData[] = (object) [
                'product_name' => $product->name,
                'tonneli' => $tonneliQty,
                'shuttle_total' => $shuttleTotal,
                'kiln_1' => $kiln1,
                'kiln_2' => $kiln2,
                'kiln_3_glaze' => $kiln3Glaze,
                'kiln_3_mum' => $kiln3Mum,
                'kiln_4' => $kiln4,
                'packaging' => $packaging,
            ];
        }

        $reportData = collect($reportData)->sortBy('product_name')->values();

        return view('reports.firing', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
            'currentYear' => $currentJalali->getYear(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  خروجی CSV گزارش پخت
    // ═══════════════════════════════════════════════════════════
    public function exportFiringCSV(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());
        $month = (int) $request->input('month', $currentJalali->getMonth());

        // ===== تونلی =====
        $tonneliItems = TonneliFiringItem::with('firing')->get();
        $filteredTonneli = $tonneliItems->filter(function ($item) use ($year, $month) {
            if (!$item->firing || !$item->firing->date) return false;
            try {
                $jalali = Jalalian::fromCarbon($item->firing->date);
                return $jalali->getYear() == $year && $jalali->getMonth() == $month;
            } catch (\Exception $e) {
                return false;
            }
        });
        $tonneliGroupQty = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->sum('output_quantity'));

        // ===== شاتل =====
        $shuttleKilns = ShuttleFiring::where('year', $year)
            ->where('month', $month)
            ->select('product_id', 'kiln_type', 'firing_subtype',
                DB::raw('SUM(output_quantity) as total_qty')
            )
            ->groupBy('product_id', 'kiln_type', 'firing_subtype')
            ->get();

        $shuttleData = [];
        foreach ($shuttleKilns as $row) {
            $productId = $row->product_id;
            $kilnType = $row->kiln_type;
            $key = ($kilnType === 'kiln_3')
                ? (($row->firing_subtype === 'glaze') ? 'kiln_3_glaze' : 'kiln_3_mum')
                : $kilnType;

            if (!isset($shuttleData[$productId])) {
                $shuttleData[$productId] = [
                    'kiln_1' => 0, 'kiln_2' => 0, 'kiln_3_glaze' => 0,
                    'kiln_3_mum' => 0, 'kiln_4' => 0, 'packaging' => 0,
                ];
            }
            if (array_key_exists($key, $shuttleData[$productId])) {
                $shuttleData[$productId][$key] += $row->total_qty;
            }
        }

        $allProductIds = $tonneliGroupQty->keys()->merge(array_keys($shuttleData))->unique();

        $filename = "گزارش_پخت_{$year}_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($allProductIds, $tonneliGroupQty, $shuttleData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, [
                'نام محصول',
                'تونلی',
                'شاتل (مجموع)',
                'کوره ۱',
                'کوره ۲',
                'کوره ۳ (لعاب)',
                'کوره ۳ (موم)',
                'کوره ۴',
                'بسته‌بندی',
            ]);

            foreach ($allProductIds as $productId) {
                $product = Product::find($productId);
                if (!$product) continue;

                $tonneliQty = $tonneliGroupQty[$productId] ?? 0;
                $kiln1 = $shuttleData[$productId]['kiln_1'] ?? 0;
                $kiln2 = $shuttleData[$productId]['kiln_2'] ?? 0;
                $kiln3Glaze = $shuttleData[$productId]['kiln_3_glaze'] ?? 0;
                $kiln3Mum = $shuttleData[$productId]['kiln_3_mum'] ?? 0;
                $kiln4 = $shuttleData[$productId]['kiln_4'] ?? 0;
                $packaging = $shuttleData[$productId]['packaging'] ?? 0;
                $shuttleTotal = $kiln1 + $kiln2 + $kiln3Glaze + $kiln3Mum + $kiln4 + $packaging;

                fputcsv($file, [
                    $product->name,
                    $tonneliQty,
                    $shuttleTotal,
                    $kiln1,
                    $kiln2,
                    $kiln3Glaze,
                    $kiln3Mum,
                    $kiln4,
                    $packaging,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ═══════════════════════════════════════════════════════════
    //  گزارش سالیانه
    // ═══════════════════════════════════════════════════════════
    public function annual(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());

        $startDate = sprintf('%04d/01/01', $year);
        $endDate = sprintf('%04d/12/29', $year);

        $productions = Production::select(
            'product_id',
            'press_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(time_hours) as total_time_hours')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('product_id', 'press_id')
        ->orderBy('product_id')
        ->orderBy('press_id')
        ->get();

        $stopData = ProductionStop::select(
            'productions.product_id',
            'productions.press_id',
            'production_stops.type',
            DB::raw('SUM(production_stops.hours) as total_hours')
        )
        ->join('productions', 'production_stops.production_id', '=', 'productions.id')
        ->where('productions.date', '>=', $startDate)
        ->where('productions.date', '<=', $endDate)
        ->groupBy('productions.product_id', 'productions.press_id', 'production_stops.type')
        ->get();

        $stopMap = [];
        foreach ($stopData as $stop) {
            $key = $stop->product_id . '-' . $stop->press_id;
            if (!isset($stopMap[$key])) {
                $stopMap[$key] = ['repair' => 0, 'breakdown' => 0];
            }
            if ($stop->type === 'تعویض قالب') {
                $stopMap[$key]['repair'] = $stop->total_hours;
            } elseif ($stop->type === 'خرابی ماشین') {
                $stopMap[$key]['breakdown'] = $stop->total_hours;
            }
        }

        $productionData = [];
        foreach ($productions as $item) {
            $productId = $item->product_id;
            $product = Product::find($productId);
            if (!$product) continue;

            $press = Press::find($item->press_id);
            $pressName = $press ? $press->name : 'بدون پرس';
            $key = $item->product_id . '-' . $item->press_id;
            $repair = $stopMap[$key]['repair'] ?? 0;
            $breakdown = $stopMap[$key]['breakdown'] ?? 0;

            if (!isset($productionData[$productId])) {
                $productionData[$productId] = [
                    'product_name' => $product->name,
                    'total_quantity' => 0,
                    'total_time_hours' => 0,
                    'repair_hours' => 0,
                    'breakdown_hours' => 0,
                    'presses' => [],
                ];
            }

            $productionData[$productId]['total_quantity'] += $item->total_quantity;
            $productionData[$productId]['total_time_hours'] += $item->total_time_hours;
            $productionData[$productId]['repair_hours'] += $repair;
            $productionData[$productId]['breakdown_hours'] += $breakdown;
            $productionData[$productId]['presses'][] = $pressName . ' (' . number_format($item->total_quantity) . ')';
        }

        $productionReport = collect($productionData)->map(function ($item) {
            return (object) [
                'product_name' => $item['product_name'],
                'total_quantity' => $item['total_quantity'],
                'total_time_hours' => $item['total_time_hours'],
                'repair_hours' => $item['repair_hours'],
                'breakdown_hours' => $item['breakdown_hours'],
                'presses_text' => implode('، ', $item['presses']) ?: '—',
            ];
        })->values()->sortBy('product_name');

        // ========== بخش پخت ==========
        $tonneliItems = TonneliFiringItem::with('firing')->get();
        $filteredTonneli = $tonneliItems->filter(function ($item) use ($year) {
            if (!$item->firing || !$item->firing->date) return false;
            try {
                $jalali = Jalalian::fromCarbon($item->firing->date);
                return $jalali->getYear() == $year;
            } catch (\Exception $e) {
                return false;
            }
        });

        $tonneliGroupQty = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->sum('output_quantity'));
        $tonneliGroupCount = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->pluck('firing_id')->unique()->count());

        $shuttleKilns = ShuttleFiring::where('year', $year)
            ->select('product_id', 'kiln_type', 'firing_subtype',
                DB::raw('SUM(output_quantity) as total_qty'),
                DB::raw('COUNT(DISTINCT (year || "-" || month || "-" || day || "-" || kiln_type || "-" || firing_number)) as firing_count')
            )
            ->groupBy('product_id', 'kiln_type', 'firing_subtype')
            ->get();

        $shuttleData = [];
        foreach ($shuttleKilns as $row) {
            $productId = $row->product_id;
            $kilnType = $row->kiln_type;
            $key = ($kilnType === 'kiln_3')
                ? (($row->firing_subtype === 'glaze') ? 'kiln_3_glaze' : 'kiln_3_mum')
                : $kilnType;

            if (!isset($shuttleData[$productId][$key])) {
                $shuttleData[$productId][$key] = ['qty' => 0, 'count' => 0];
            }
            $shuttleData[$productId][$key]['qty'] += $row->total_qty;
            $shuttleData[$productId][$key]['count'] += $row->firing_count;
        }

        $allProductIds = $tonneliGroupQty->keys()->merge($tonneliGroupCount->keys())->merge(array_keys($shuttleData))->unique();
        $firingReport = collect();
        foreach ($allProductIds as $productId) {
            $product = Product::find($productId);
            if (!$product) continue;

            $tonneliQty = $tonneliGroupQty[$productId] ?? 0;
            $tonneliCount = $tonneliGroupCount[$productId] ?? 0;

            $kiln1 = $shuttleData[$productId]['kiln_1'] ?? ['qty' => 0, 'count' => 0];
            $kiln2 = $shuttleData[$productId]['kiln_2'] ?? ['qty' => 0, 'count' => 0];
            $kiln3Glaze = $shuttleData[$productId]['kiln_3_glaze'] ?? ['qty' => 0, 'count' => 0];
            $kiln3Mum = $shuttleData[$productId]['kiln_3_mum'] ?? ['qty' => 0, 'count' => 0];
            $kiln4 = $shuttleData[$productId]['kiln_4'] ?? ['qty' => 0, 'count' => 0];
            $packaging = $shuttleData[$productId]['packaging'] ?? ['qty' => 0, 'count' => 0];

            $shuttleTotalQty = $kiln1['qty'] + $kiln2['qty'] + $kiln3Glaze['qty'] + $kiln3Mum['qty'] + $kiln4['qty'] + $packaging['qty'];
            $shuttleTotalCount = $kiln1['count'] + $kiln2['count'] + $kiln3Glaze['count'] + $kiln3Mum['count'] + $kiln4['count'] + $packaging['count'];

            $firingReport->push((object) [
                'product_name' => $product->name,
                'tonneli_qty' => $tonneliQty,
                'tonneli_count' => $tonneliCount,
                'shuttle_total_qty' => $shuttleTotalQty,
                'shuttle_total_count' => $shuttleTotalCount,
                'kiln_1_qty' => $kiln1['qty'],
                'kiln_1_count' => $kiln1['count'],
                'kiln_2_qty' => $kiln2['qty'],
                'kiln_2_count' => $kiln2['count'],
                'kiln_3_glaze_qty' => $kiln3Glaze['qty'],
                'kiln_3_glaze_count' => $kiln3Glaze['count'],
                'kiln_3_mum_qty' => $kiln3Mum['qty'],
                'kiln_3_mum_count' => $kiln3Mum['count'],
                'kiln_4_qty' => $kiln4['qty'],
                'kiln_4_count' => $kiln4['count'],
                'packaging_qty' => $packaging['qty'],
                'packaging_count' => $packaging['count'],
            ]);
        }
        $firingReport = $firingReport->sortBy('product_name')->values();

        return view('reports.annual', [
            'productionReport' => $productionReport,
            'firingReport' => $firingReport,
            'year' => $year,
            'currentYear' => $currentJalali->getYear(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  خروجی CSV گزارش سالیانه
    // ═══════════════════════════════════════════════════════════
    public function exportAnnualCSV(Request $request)
    {
        $year = (int) $request->input('year', Jalalian::now()->getYear());

        $startDate = sprintf('%04d/01/01', $year);
        $endDate = sprintf('%04d/12/29', $year);

        $productions = Production::select(
            'product_id',
            'press_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(time_hours) as total_time_hours')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('product_id', 'press_id')
        ->get();

        $stopData = ProductionStop::select(
            'productions.product_id',
            'productions.press_id',
            'production_stops.type',
            DB::raw('SUM(production_stops.hours) as total_hours')
        )
        ->join('productions', 'production_stops.production_id', '=', 'productions.id')
        ->where('productions.date', '>=', $startDate)
        ->where('productions.date', '<=', $endDate)
        ->groupBy('productions.product_id', 'productions.press_id', 'production_stops.type')
        ->get();

        $stopMap = [];
        foreach ($stopData as $stop) {
            $key = $stop->product_id . '-' . $stop->press_id;
            if (!isset($stopMap[$key])) {
                $stopMap[$key] = ['repair' => 0, 'breakdown' => 0];
            }
            if ($stop->type === 'تعویض قالب') {
                $stopMap[$key]['repair'] = $stop->total_hours;
            } elseif ($stop->type === 'خرابی ماشین') {
                $stopMap[$key]['breakdown'] = $stop->total_hours;
            }
        }

        $productionData = [];
        foreach ($productions as $item) {
            $product = Product::find($item->product_id);
            if (!$product) continue;
            $key = $item->product_id . '-' . $item->press_id;
            $repair = $stopMap[$key]['repair'] ?? 0;
            $breakdown = $stopMap[$key]['breakdown'] ?? 0;

            if (!isset($productionData[$item->product_id])) {
                $productionData[$item->product_id] = [
                    'product_name' => $product->name,
                    'total_quantity' => 0,
                    'total_time_hours' => 0,
                    'repair_hours' => 0,
                    'breakdown_hours' => 0,
                ];
            }
            $productionData[$item->product_id]['total_quantity'] += $item->total_quantity;
            $productionData[$item->product_id]['total_time_hours'] += $item->total_time_hours;
            $productionData[$item->product_id]['repair_hours'] += $repair;
            $productionData[$item->product_id]['breakdown_hours'] += $breakdown;
        }

        $tonneliItems = TonneliFiringItem::with('firing')->get();
        $filteredTonneli = $tonneliItems->filter(function ($item) use ($year) {
            if (!$item->firing || !$item->firing->date) return false;
            try {
                $jalali = Jalalian::fromCarbon($item->firing->date);
                return $jalali->getYear() == $year;
            } catch (\Exception $e) {
                return false;
            }
        });
        $tonneliGroupQty = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->sum('output_quantity'));
        $tonneliGroupCount = $filteredTonneli->groupBy('product_id')->map(fn($items) => $items->pluck('firing_id')->unique()->count());

        $shuttleKilns = ShuttleFiring::where('year', $year)
            ->select('product_id', 'kiln_type', 'firing_subtype',
                DB::raw('SUM(output_quantity) as total_qty'),
                DB::raw('COUNT(DISTINCT (year || "-" || month || "-" || day || "-" || kiln_type || "-" || firing_number)) as firing_count')
            )
            ->groupBy('product_id', 'kiln_type', 'firing_subtype')
            ->get();

        $shuttleData = [];
        foreach ($shuttleKilns as $row) {
            $productId = $row->product_id;
            $kilnType = $row->kiln_type;
            $key = ($kilnType === 'kiln_3')
                ? (($row->firing_subtype === 'glaze') ? 'kiln_3_glaze' : 'kiln_3_mum')
                : $kilnType;
            if (!isset($shuttleData[$productId][$key])) {
                $shuttleData[$productId][$key] = ['qty' => 0, 'count' => 0];
            }
            $shuttleData[$productId][$key]['qty'] += $row->total_qty;
            $shuttleData[$productId][$key]['count'] += $row->firing_count;
        }

        $allIds = array_keys($productionData);
        $allIds = array_unique(array_merge($allIds, $tonneliGroupQty->keys()->toArray(), array_keys($shuttleData)));

        $rows = [];
        foreach ($allIds as $id) {
            $product = Product::find($id);
            if (!$product) continue;

            $prod = $productionData[$id] ?? ['total_quantity' => 0, 'total_time_hours' => 0, 'repair_hours' => 0, 'breakdown_hours' => 0];
            $tonneliQty = $tonneliGroupQty[$id] ?? 0;
            $tonneliCount = $tonneliGroupCount[$id] ?? 0;

            $kiln1 = $shuttleData[$id]['kiln_1'] ?? ['qty' => 0, 'count' => 0];
            $kiln2 = $shuttleData[$id]['kiln_2'] ?? ['qty' => 0, 'count' => 0];
            $kiln3Glaze = $shuttleData[$id]['kiln_3_glaze'] ?? ['qty' => 0, 'count' => 0];
            $kiln3Mum = $shuttleData[$id]['kiln_3_mum'] ?? ['qty' => 0, 'count' => 0];
            $kiln4 = $shuttleData[$id]['kiln_4'] ?? ['qty' => 0, 'count' => 0];
            $packaging = $shuttleData[$id]['packaging'] ?? ['qty' => 0, 'count' => 0];

            $shuttleTotalQty = $kiln1['qty'] + $kiln2['qty'] + $kiln3Glaze['qty'] + $kiln3Mum['qty'] + $kiln4['qty'] + $packaging['qty'];
            $shuttleTotalCount = $kiln1['count'] + $kiln2['count'] + $kiln3Glaze['count'] + $kiln3Mum['count'] + $kiln4['count'] + $packaging['count'];

            $rows[] = [
                'product_name' => $product->name,
                'total_quantity' => $prod['total_quantity'],
                'total_time_hours' => $prod['total_time_hours'],
                'repair_hours' => $prod['repair_hours'],
                'breakdown_hours' => $prod['breakdown_hours'],
                'tonneli_qty' => $tonneliQty,
                'tonneli_count' => $tonneliCount,
                'shuttle_total_qty' => $shuttleTotalQty,
                'shuttle_total_count' => $shuttleTotalCount,
                'kiln_1_qty' => $kiln1['qty'],
                'kiln_1_count' => $kiln1['count'],
                'kiln_2_qty' => $kiln2['qty'],
                'kiln_2_count' => $kiln2['count'],
                'kiln_3_glaze_qty' => $kiln3Glaze['qty'],
                'kiln_3_glaze_count' => $kiln3Glaze['count'],
                'kiln_3_mum_qty' => $kiln3Mum['qty'],
                'kiln_3_mum_count' => $kiln3Mum['count'],
                'kiln_4_qty' => $kiln4['qty'],
                'kiln_4_count' => $kiln4['count'],
                'packaging_qty' => $packaging['qty'],
                'packaging_count' => $packaging['count'],
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['product_name'], $b['product_name']));

        $filename = "گزارش_سالیانه_{$year}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, [
                'نام محصول',
                'مجموع تولید',
                'زمان کارکرد (ساعت)',
                'تعویض قالب (ساعت)',
                'خرابی ماشین (ساعت)',
                'تونلی (تعداد قطعات)',
                'تونلی (تعداد پخت)',
                'شاتل (مجموع قطعات)',
                'شاتل (مجموع پخت)',
                'کوره ۱ (قطعات)',
                'کوره ۱ (پخت)',
                'کوره ۲ (قطعات)',
                'کوره ۲ (پخت)',
                'کوره ۳ لعاب (قطعات)',
                'کوره ۳ لعاب (پخت)',
                'کوره ۳ موم (قطعات)',
                'کوره ۳ موم (پخت)',
                'کوره ۴ (قطعات)',
                'کوره ۴ (پخت)',
                'بسته‌بندی (قطعات)',
                'بسته‌بندی (پخت)',
            ]);
            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['product_name'],
                    $row['total_quantity'],
                    $row['total_time_hours'],
                    $row['repair_hours'],
                    $row['breakdown_hours'],
                    $row['tonneli_qty'],
                    $row['tonneli_count'],
                    $row['shuttle_total_qty'],
                    $row['shuttle_total_count'],
                    $row['kiln_1_qty'],
                    $row['kiln_1_count'],
                    $row['kiln_2_qty'],
                    $row['kiln_2_count'],
                    $row['kiln_3_glaze_qty'],
                    $row['kiln_3_glaze_count'],
                    $row['kiln_3_mum_qty'],
                    $row['kiln_3_mum_count'],
                    $row['kiln_4_qty'],
                    $row['kiln_4_count'],
                    $row['packaging_qty'],
                    $row['packaging_count'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}