<?php

namespace App\Http\Controllers;

use App\Models\ShuttleFiring;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ShuttleFiringController extends Controller
{
    public function index()
    {
        $firings = ShuttleFiring::with('product')->latest('date')->paginate(15);
        return view('shuttle.index', compact('firings'));
    }

    public function create()
    {
        $products = Product::where('status', true)->whereIn('kiln_type', ['shuttle', 'both'])->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('shuttle.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|string',
            'kiln_type' => 'required|in:kiln_1,kiln_2,kiln_3,packaging',
            'firing_subtype' => 'nullable|required_if:kiln_type,kiln_3|in:mum,glaze',
            'product_id' => 'required|exists:products,id',
            'output_quantity' => 'nullable|numeric|min:0',
            'is_packaged' => 'boolean',
        ]);

        try {
            $data['date'] = Jalalian::fromFormat('Y/m/d', $data['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        // شماره پخت خودکار ماهانه
        $jalaliDate = Jalalian::fromCarbon($data['date']);
        $year = $jalaliDate->getYear();
        $month = $jalaliDate->getMonth();

        $lastFiring = ShuttleFiring::where('kiln_type', $data['kiln_type'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('firing_number', 'desc')
            ->first();

        $nextNumber = $lastFiring ? intval($lastFiring->firing_number) + 1 : 1;
        $data['firing_number'] = $nextNumber;

        $data['output_quantity'] = $data['output_quantity'] ?? null;
        $data['is_packaged'] = $request->has('is_packaged');

        ShuttleFiring::create($data);
        return redirect()->route('shuttle.create')->with('success', 'ثبت شد.');
    }

    public function destroy(ShuttleFiring $shuttle)
    {
        $shuttle->delete();
        return redirect()->route('shuttle.index')->with('success', 'حذف شد.');
    }
}