<?php

namespace App\Http\Controllers;

use App\Models\InventoryChangeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class InventoryLogController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryChangeLog::with(['user', 'loggable'])
            ->orderBy('created_at', 'desc');

        // ✅ فیلتر نوع موجودی
        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $typeMap = [
                    'warehouse'   => 'App\Models\WarehouseInventory',
                    'raw'         => 'App\Models\RawInventory',
                    'wax'         => 'App\Models\WaxInventory',
                    'shoulder'    => 'App\Models\ShoulderInventory',
                    'waste_mum'   => 'App\Models\WasteMumInventory',
                    'glaze1300'   => 'App\Models\Glaze1300Inventory',
                    'raw_material'=> 'App\Models\RawMaterial',
                    'packaging'   => 'App\Models\Packaging',
                ];
                if (isset($typeMap[$type])) {
                    $query->where('loggable_type', $typeMap[$type]);
                }
            }
        }

        // ✅ فیلتر منبع
        if ($source = $request->input('source')) {
            if ($source !== 'all') {
                $query->where('source', 'like', $source . '%');
            }
        }

        // ✅ فیلتر کاربر
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        // ✅ جستجو
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%");
            });
        }

        // ✅ فیلتر بازه تاریخ (شمسی)
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $quick    = $request->input('quick');

        // ✅ دکمه‌های سریع
        if ($quick && !$dateFrom && !$dateTo) {
            $now = Jalalian::now();
            $today = $now->toCarbon();

            switch ($quick) {
                case 'today':
                    $from = $today->copy()->startOfDay();
                    $to   = $today->copy()->endOfDay();
                    break;

                case 'yesterday':
                    $from = $today->copy()->subDay()->startOfDay();
                    $to   = $today->copy()->subDay()->endOfDay();
                    break;

                case 'this_week':
                    // هفته در ایران از شنبه شروع می‌شود
                    $from = $today->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->startOfDay();
                    $to   = now()->endOfDay();
                    break;

                case 'this_month':
                    // اول ماه شمسی
                    $firstOfMonth = Jalalian::fromFormat(
                        'Y/m/d',
                        $now->getYear() . '/' . str_pad($now->getMonth(), 2, '0', STR_PAD_LEFT) . '/01'
                    )->toCarbon();
                    $from = $firstOfMonth->startOfDay();
                    $to   = now()->endOfDay();
                    break;

                default:
                    $from = null;
                    $to = null;
            }

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
                // پرشونده input‌های تاریخ برای نمایش در فیلدها
                $dateFrom = Jalalian::fromCarbon($from)->format('Y/m/d');
                $dateTo   = Jalalian::fromCarbon($to)->format('Y/m/d');
            }
        }
        // ✅ فیلتر دستی بازه تاریخ
        else {
            if ($dateFrom) {
                try {
                    $from = Jalalian::fromFormat('Y/m/d', $dateFrom)->toCarbon()->startOfDay();
                    $query->where('created_at', '>=', $from);
                } catch (\Exception $e) { /* ignore */ }
            }
            if ($dateTo) {
                try {
                    $to = Jalalian::fromFormat('Y/m/d', $dateTo)->toCarbon()->endOfDay();
                    $query->where('created_at', '<=', $to);
                } catch (\Exception $e) { /* ignore */ }
            }
        }

        $logs = $query->paginate(30)->appends($request->all());

        // آمار دسته‌بندی‌ها
        $typeCounts = [
            'all'          => InventoryChangeLog::count(),
            'warehouse'    => InventoryChangeLog::where('loggable_type', 'App\Models\WarehouseInventory')->count(),
            'raw'          => InventoryChangeLog::where('loggable_type', 'App\Models\RawInventory')->count(),
            'wax'          => InventoryChangeLog::where('loggable_type', 'App\Models\WaxInventory')->count(),
            'shoulder'     => InventoryChangeLog::where('loggable_type', 'App\Models\ShoulderInventory')->count(),
            'waste_mum'    => InventoryChangeLog::where('loggable_type', 'App\Models\WasteMumInventory')->count(),
            'glaze1300'    => InventoryChangeLog::where('loggable_type', 'App\Models\Glaze1300Inventory')->count(),
            'raw_material' => InventoryChangeLog::where('loggable_type', 'App\Models\RawMaterial')->count(),
            'packaging'    => InventoryChangeLog::where('loggable_type', 'App\Models\Packaging')->count(),
        ];

        $users = User::orderBy('name')->get();

        // برای نمایش در فیلدها
        $currentDateFrom = $dateFrom;
        $currentDateTo   = $dateTo;
        $currentQuick    = $quick;

        return view('inventory-logs.index', compact(
            'logs', 'typeCounts', 'users',
            'currentDateFrom', 'currentDateTo', 'currentQuick'
        ));
    }

    /**
     * ✅ پاک کردن لاگ‌های قدیمی
     */
    public function deleteOld(Request $request)
    {
        $request->validate([
            'period' => 'required|in:1_month,3_months,6_months,1_year,all',
        ]);

        $period = $request->input('period');
        $count = 0;

        if ($period === 'all') {
            $count = InventoryChangeLog::count();
            InventoryChangeLog::query()->delete();
            $msg = "✅ تمام {$count} لاگ پاک شد.";
        } else {
            $cutoff = match ($period) {
                '1_month'  => now()->subMonth(),
                '3_months' => now()->subMonths(3),
                '6_months' => now()->subMonths(6),
                '1_year'   => now()->subYear(),
                default    => now()->subMonths(6),
            };

            $count = InventoryChangeLog::where('created_at', '<', $cutoff)->count();
            InventoryChangeLog::where('created_at', '<', $cutoff)->delete();

            $labels = [
                '1_month'  => '۱ ماه',
                '3_months' => '۳ ماه',
                '6_months' => '۶ ماه',
                '1_year'   => '۱ سال',
            ];

            $msg = "✅ {$count} لاگ قدیمی‌تر از {$labels[$period]} پاک شد.";
        }

        return back()->with('success', $msg);
    }
}