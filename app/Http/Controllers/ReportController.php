<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * نمایش گزارش تولید
     */
    public function production()
    {
        return view('reports.production');
    }

    /**
     * نمایش گزارش پخت
     */
    public function firing()
    {
        return view('reports.firing');
    }

    /**
     * نمایش گزارش سالیانه
     */
    public function annual()
    {
        return view('reports.annual');
    }
}