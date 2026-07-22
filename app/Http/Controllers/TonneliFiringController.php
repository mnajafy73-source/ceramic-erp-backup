<?php

namespace App\Http\Controllers;

use App\Models\TonneliFiring;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class TonneliFiringController extends Controller
{
    public function index()
    {
        $firings = TonneliFiring::with('product')->latest('date')->paginate(15);
        return view('tonneli.index', compact('firings'));
    }

    public function create()
    {
        $products = Product::where('status', true)->whereIn('kiln_type', ['tonneli', 'both'])->get();
        $yesterday = Jalalian::fromCarbon(now()->subDay())->format('Y/m/d');
        return view('tonneli.create', compact('products', 'yesterday'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|string',
            'product_id' => 'required|exists:products,id',
            'input_quantity' => 'nullable|numeric|min:0',
            'output_quantity' => 'nullable|numeric|min:0',
            'is_packaged' => 'boolean',
        ]);

        try {
            $data['date'] = Jalalian::fromFormat('Y/m/d', $data['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $data['input_quantity'] = $data['input_quantity'] ?? 0;
        $data['output_quantity'] = $data['output_quantity'] ?? 0;
        $data['is_packaged'] = $request->has('is_packaged');

        TonneliFiring::create($data);
        return redirect()->to(url('/tonneli/create'))->with('success', 'ثبت شد.');
    }

    public function destroy(TonneliFiring $tonneli)
    {
        $tonneli->delete();
        return redirect()->to(url('/tonneli'))->with('success', 'حذف شد.');
    }

    // سایر متدها بدون تغییر (show, edit, update, togglePackaged) طبق آخرین نسخه
    // ...
}