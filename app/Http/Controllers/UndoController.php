<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UndoController extends Controller
{
    public function restore(Request $request)
    {
        $record = session('undo_record');

        if (!$record) {
            return back()->with('error', 'امکان برگشت وجود ندارد.');
        }

        $class = $record['class'];
        $data = $record['data'];

        // 🔥 اگر داده یک آرایه از چند رکورد است (حذف گروهی)
        if (isset($data[0]) && is_array($data[0])) {
            // بازگردانی چند رکورد
            foreach ($data as $item) {
                $this->insertRecord($class, $item);
            }
        } else {
            // بازگردانی یک رکورد (حذف تکی)
            $this->insertRecord($class, $data);
        }

        // پاک کردن سشن
        session()->forget('undo_record');

        return back()->with('success', 'عملیات با موفقیت برگشت داده شد.');
    }

    // متد کمکی برای درج یک رکورد
    private function insertRecord($class, $data)
    {
        $data['created_at'] = now();
        $data['updated_at'] = now();

        // تبدیل فیلد تاریخ به فرمت صحیح
        foreach ($data as $key => $value) {
            if ($key === 'date' && is_string($value) && strpos($value, 'T') !== false) {
                try {
                    $data[$key] = Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    // اگر تبدیل نشد، مقدار را به همان صورت نگه دار
                }
            }
        }

        DB::table((new $class())->getTable())->insert($data);
    }

    public function discard(Request $request)
    {
        session()->forget('undo_record');
        return back()->with('success', 'بازیابی لغو شد.');
    }
}