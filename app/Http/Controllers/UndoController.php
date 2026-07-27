<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table((new $class())->getTable())->insert($data);

        session()->forget('undo_record');

        return back()->with('success', 'عملیات با موفقیت برگشت داده شد.');
    }

    public function discard(Request $request)
    {
        session()->forget('undo_record');
        return back()->with('success', 'بازیابی لغو شد.');
    }
}