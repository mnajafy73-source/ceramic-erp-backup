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
        $validated = $request->validate([
            'date' => 'required|string',
            'product_id' => 'required|exists:products,id',
            'input_quantity' => 'nullable|numeric|min:0',
            'output_quantity' => 'nullable|numeric|min:0',
            'is_packaged' => 'boolean',
        ]);

        try {
            $validated['date'] = Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        $validated['input_quantity'] = $validated['input_quantity'] ?? 0;
        $validated['output_quantity'] = $validated['output_quantity'] ?? 0;
        $validated['is_packaged'] = $request->has('is_packaged');

        TonneliFiring::create($validated);
        return redirect()->route('tonneli.create')->with('success', 'پخت تونلی با موفقیت ثبت شد.');
    }

    // حذف با قابلیت Undo
    public function destroy(TonneliFiring $tonneli)
    {
        $tonneli->delete();
        return redirect()->to('/tonneli')->with('success', 'حذف شد.');
    }

    // سایر متدها (show, edit, update) مستقیماً در web.php هندل می‌شوند
}