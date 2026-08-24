<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ReportController extends Controller
{
    /**
     * نمایش گزارش تولید با فیلتر ماهانه (گروه‌بندی بر اساس تاریخ و محصول)
     */
    public function production(Request $request)
    {
        $currentJalali = Jalalian::now();
        $month = (int) $request->input('month', $currentJalali->getMonth());
        $year  = (int) $request->input('year', $currentJalali->getYear());

        // ساخت تاریخ شمسی به‌صورت رشته
        $startDate = sprintf('%04d/%02d/01', $year, $month);
        $lastDay = Jalalian::fromFormat('Y/m/d', $startDate)->getMonthDays();
        $endDate = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

        // دریافت تولیدات گروه‌بندی شده بر اساس تاریخ و محصول
        $productions = Production::select(
            'date',
            'product_id',
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('group_concat(distinct press_id) as press_ids'),
            DB::raw('group_concat(distinct stage) as stages'),
            DB::raw('group_concat(distinct operator_id) as operator_ids')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('date', 'product_id')
        ->orderBy('date', 'desc')
        ->orderBy('product_id')
        ->get();

        // بارگذاری اطلاعات مرتبط
        $reportData = $productions->map(function ($item) {
            // دریافت نام محصول
            $product = \App\Models\Product::find($item->product_id);
            $productName = $product ? $product->name : 'نامشخص';

            // دریافت نام پرس‌ها
            $pressIds = array_filter(explode(',', $item->press_ids ?? ''));
            $pressNames = \App\Models\Press::whereIn('id', $pressIds)->pluck('name')->implode('، ');

            // دریافت عملیات‌ها
            $stages = array_filter(explode(',', $item->stages ?? ''));
            $stagesText = implode('، ', $stages) ?: '-';

            // دریافت اپراتورها
            $operatorIds = array_filter(explode(',', $item->operator_ids ?? ''));
            $operatorNames = \App\Models\Operator::whereIn('id', $operatorIds)->pluck('name')->implode('، ');

            // دریافت توقف‌ها (مجموع ساعات بر اساس نوع) - برای همه رکوردهای این گروه
            $stopData = ProductionStop::whereIn('production_id', function($query) use ($item) {
                $query->select('id')
                    ->from('productions')
                    ->where('date', $item->date)
                    ->where('product_id', $item->product_id);
            })
            ->select('type', DB::raw('sum(hours) as total_hours'))
            ->groupBy('type')
            ->get()
            ->pluck('total_hours', 'type')
            ->toArray();

            $repairHours = $stopData['تعویض قالب'] ?? 0;
            $breakdownHours = $stopData['خرابی ماشین'] ?? 0;

            return [
                'product_name'      => $productName,
                'press_name'        => $pressNames ?: '-',
                'stage'             => $stagesText,
                'cavities'          => $product ? $product->cavities : 0,
                'quantity'          => $item->total_quantity,
                'repair_hours'      => $repairHours,
                'breakdown_hours'   => $breakdownHours,
                'date'              => $item->date,
                'operator'          => $operatorNames ?: '-',
            ];
        });

        return view('reports.production', [
            'reportData' => $reportData,
            'month'      => $month,
            'year'       => $year,
            'currentYear' => $currentJalali->getYear(),
        ]);
    }

    /**
     * خروجی CSV از گزارش تولید (گروه‌بندی شده)
     */
    public function exportProductionCSV(Request $request)
    {
        $currentJalali = Jalalian::now();
        $month = (int) $request->input('month', $currentJalali->getMonth());
        $year  = (int) $request->input('year', $currentJalali->getYear());

        $startDate = sprintf('%04d/%02d/01', $year, $month);
        $lastDay = Jalalian::fromFormat('Y/m/d', $startDate)->getMonthDays();
        $endDate = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

        $productions = Production::select(
            'date',
            'product_id',
            DB::raw('sum(quantity) as total_quantity'),
            DB::raw('group_concat(distinct press_id) as press_ids'),
            DB::raw('group_concat(distinct stage) as stages'),
            DB::raw('group_concat(distinct operator_id) as operator_ids')
        )
        ->where('date', '>=', $startDate)
        ->where('date', '<=', $endDate)
        ->groupBy('date', 'product_id')
        ->orderBy('date', 'desc')
        ->orderBy('product_id')
        ->get();

        $filename = "گزارش_تولید_ماه_{$year}_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($productions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'نام قطعه',
                'دستگاه',
                'عملیات',
                'حفره',
                'تعداد',
                'تعویض قالب (ساعت)',
                'خرابی ماشین (ساعت)',
                'تاریخ'
            ]);

            foreach ($productions as $item) {
                $product = \App\Models\Product::find($item->product_id);
                $productName = $product ? $product->name : 'نامشخص';

                $pressIds = array_filter(explode(',', $item->press_ids ?? ''));
                $pressNames = \App\Models\Press::whereIn('id', $pressIds)->pluck('name')->implode('، ');

                $stages = array_filter(explode(',', $item->stages ?? ''));
                $stagesText = implode('، ', $stages) ?: '-';

                $stopData = ProductionStop::whereIn('production_id', function($query) use ($item) {
                    $query->select('id')
                        ->from('productions')
                        ->where('date', $item->date)
                        ->where('product_id', $item->product_id);
                })
                ->select('type', DB::raw('sum(hours) as total_hours'))
                ->groupBy('type')
                ->get()
                ->pluck('total_hours', 'type')
                ->toArray();

                $repairHours = $stopData['تعویض قالب'] ?? 0;
                $breakdownHours = $stopData['خرابی ماشین'] ?? 0;

                fputcsv($file, [
                    $productName,
                    $pressNames ?: '-',
                    $stagesText,
                    $product ? $product->cavities : 0,
                    $item->total_quantity,
                    $repairHours,
                    $breakdownHours,
                    $item->date,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * گزارش پخت (فعلاً خالی)
     */
    public function firing()
    {
        return view('reports.firing');
    }

    /**
     * گزارش سالیانه (فعلاً خالی)
     */
    public function annual()
    {
        return view('reports.annual');
    }
}