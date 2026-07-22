<?php

namespace App\Http\Controllers;

use App\Models\ProductLog;
use Illuminate\Http\Request;

class ProductLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductLog::with(['product', 'user'])->latest();

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->appends($request->all());

        // تبدیل changes به آرایهٔ فارسی‌شده
        foreach ($logs as $log) {
            if ($log->changes) {
                $changes = json_decode($log->changes, true);
                $persianChanges = [];
                $fieldNames = [
                    'name' => 'نام',
                    'code' => 'کد',
                    'unit_id' => 'واحد',
                    'initial_stock' => 'موجودی اولیه',
                    'firing_process' => 'فرآیند پخت',
                    'kiln_type' => 'نوع کوره',
                    'tonneli_feed_rate' => 'خوراک تونلی',
                    'cavities' => 'حفره',
                    'per_box' => 'کارتن',
                    'per_pack' => 'بسته',
                    'per_pallet' => 'پالت',
                    'box_type' => 'نوع کارتن',
                    'layers_per_box' => 'لایه/کارتن',
                    'status' => 'وضعیت',
                    'in_production' => 'در حال تولید',
                    'description' => 'توضیحات',
                ];
                foreach ($changes as $field => $value) {
                    $persianField = $fieldNames[$field] ?? $field;
                    if ($field === 'status' || $field === 'in_production') {
                        $value = $value ? 'فعال/بله' : 'غیرفعال/خیر';
                    }
                    $persianChanges[] = "$persianField: $value";
                }
                $log->persian_changes = implode(' | ', $persianChanges);
            } else {
                $log->persian_changes = null;
            }
        }

        return view('product_logs.index', compact('logs'));
    }
}