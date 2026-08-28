<?php

namespace App\Http\Controllers;

use App\Models\InformalSale;
use App\Models\InformalSaleProduct;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class InformalSaleController extends Controller
{
    public function index()
    {
        $sales = InformalSale::with('customer', 'products.product')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);
        return view('informal-sales.index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $today = Jalalian::now()->format('Y/m/d');
        return view('informal-sales.create', compact('customers', 'products', 'today'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_if:customer_id,null|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'status' => 'nullable|in:unpaid,paid,canceled',
        ]);

        // تاریخ شمسی به میلادی
        try {
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $validated['date']);
            $gregorianDate = $jalaliDate->toCarbon();
            $year = $jalaliDate->getYear();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
        }

        // مشتری
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $customerName = $customer->name;
        } else {
            $customer = Customer::firstOrCreate(
                ['name' => $validated['customer_name']],
                ['status' => 1]
            );
            $customerName = $customer->name;
        }

        // محاسبه قیمت کل
        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $totalPrice += $item['quantity'] * $item['unit_price'];
        }

        // شماره فاکتور خودکار
        $maxNumber = InformalSale::where('year', $year)->max('number') ?? 0;
        $number = $maxNumber + 1;

        // ایجاد فاکتور
        $sale = InformalSale::create([
            'year' => $year,
            'number' => $number,
            'date' => $gregorianDate,
            'customer_id' => $customer->id,
            'customer_name' => $customerName,
            'total_price' => $totalPrice,
            'status' => $validated['status'] ?? 'unpaid',
        ]);

        // آیتم‌ها
        foreach ($validated['items'] as $item) {
            InformalSaleProduct::create([
                'informal_sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        return redirect()->route('informal-sales.index')
            ->with('success', 'فروش غیررسمی با موفقیت ثبت شد.');
    }

    public function show(InformalSale $informalSale)
    {
        $informalSale->load('products.product', 'customer');
        return view('informal-sales.show', compact('informalSale'));
    }

    public function edit(InformalSale $informalSale)
    {
        $customers = Customer::where('status', 1)->orderBy('name')->get();
        $products = Product::where('status', 1)->orderBy('name')->get();
        $informalSale->load('products');
        $informalSale->jalali_date = Jalalian::fromCarbon($informalSale->date)->format('Y/m/d');
        return view('informal-sales.edit', compact('informalSale', 'customers', 'products'));
    }

    public function update(Request $request, InformalSale $informalSale)
    {
        // ... (مشابه store با تغییرات)
        // برای اختصار، کد کامل در فایل کامل ارسال می‌شود
    }

    public function destroy(InformalSale $informalSale)
    {
        $informalSale->products()->delete();
        $informalSale->delete();
        return redirect()->route('informal-sales.index')
            ->with('success', 'فروش غیررسمی حذف شد.');
    }

    public function markAsPaid(InformalSale $informalSale)
    {
        $informalSale->update(['status' => 'paid']);
        return redirect()->route('informal-sales.index')
            ->with('success', 'وضعیت به پرداخت شده تغییر یافت.');
    }

    public function cancel(InformalSale $informalSale)
    {
        $informalSale->update(['status' => 'canceled']);
        return redirect()->route('informal-sales.index')
            ->with('success', 'فاکتور لغو شد.');
    }
}