<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\InformalSale;
use App\Models\InformalSaleProduct;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * نمایش فرم تست
     */
    public function index()
    {
        return view('test.index');
    }

    /**
     * ثبت فروش تست
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'customer_name' => 'required|string|max:255',
                'product_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|numeric|min:0',
            ]);

            // پیدا کردن یا ایجاد مشتری
            $customer = Customer::firstOrCreate(
                ['name' => $request->customer_name],
                ['status' => 1]
            );

            // پیدا کردن یا ایجاد محصول
            $product = Product::firstOrCreate(
                ['name' => $request->product_name],
                [
                    'code' => 'TEST-' . time(),
                    'status' => 1,
                    'cavities' => 0,
                    'weight' => 0,
                    'per_box' => 0,
                    'layers_per_box' => 0,
                    'firing_process' => 'tonneli',
                ]
            );

            // ثبت فاکتور
            $sale = InformalSale::create([
                'year' => 1405,
                'number' => rand(1000, 9999),
                'date' => now(),
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'total_price' => $request->quantity * $request->unit_price,
                'status' => 'unpaid',
            ]);

            // ثبت آیتم فاکتور
            InformalSaleProduct::create([
                'informal_sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
            ]);

            return redirect()->route('test.index')
                ->with('success', "✅ فاکتور با موفقیت ثبت شد. شماره: {$sale->number}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'خطا: ' . $e->getMessage()])->withInput();
        }
    }
}