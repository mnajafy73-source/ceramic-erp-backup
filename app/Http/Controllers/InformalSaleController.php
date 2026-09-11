<?php

namespace App\Http\Controllers;

use App\Models\InformalSale;
use App\Models\InformalSaleProduct;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class InformalSaleController extends Controller
{
    // ==================== متدهای کمکی موجودی ====================

    private function calculateBoxAndLayer($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['box' => 0, 'layer' => 0, 'pallet' => 0];
        }

        $box = 0;
        $layer = 0;
        $pallet = 0;

        if ($product->per_box && $product->per_box > 0) {
            $box = intval($quantity / $product->per_box);
        }
        if ($product->layers_per_box && $product->layers_per_box > 0 && $product->per_box > 0) {
            $perLayer = $product->per_box * $product->layers_per_box;
            $layer = intval($quantity / $perLayer);
        }
        if ($product->per_pallet && $product->per_pallet > 0) {
            $pallet = intval($quantity / $product->per_pallet);
        }

        return ['box' => $box, 'layer' => $layer, 'pallet' => $pallet];
    }

    private function decreaseStock($productId, $quantity, $box, $layer, $pallet)
    {
        $inventory = Inventory::firstOrCreate(['product_id' => $productId]);
        $inventory->quantity = max(0, $inventory->quantity - $quantity);
        $inventory->box = max(0, $inventory->box - $box);
        $inventory->layer = max(0, $inventory->layer - $layer);
        $inventory->pallet = max(0, $inventory->pallet - $pallet);
        $inventory->save();
    }

    private function increaseStock($productId, $quantity, $box, $layer, $pallet)
    {
        $inventory = Inventory::firstOrCreate(['product_id' => $productId]);
        $inventory->quantity += $quantity;
        $inventory->box += $box;
        $inventory->layer += $layer;
        $inventory->pallet += $pallet;
        $inventory->save();
    }

    // ==================== متدهای اصلی ====================

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

        DB::beginTransaction();

        try {
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

            // آیتم‌ها + کسر موجودی
            foreach ($validated['items'] as $item) {
                InformalSaleProduct::create([
                    'informal_sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                // ✅ کسر موجودی (فقط اگه فاکتور لغو نشده باشه)
                if (($validated['status'] ?? 'unpaid') !== 'canceled') {
                    $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                    $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            DB::commit();

            return redirect()->route('informal-sales.index')
                ->with('success', 'فروش غیررسمی با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت: ' . $e->getMessage()])->withInput();
        }
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

        // وضعیت جدید
        $newStatus = $validated['status'] ?? $informalSale->status;

        DB::beginTransaction();

        try {
            // ✅ برگرداندن موجودی قبلی (فقط اگه فاکتور قبلاً لغو نشده بود)
            if ($informalSale->status !== 'canceled') {
                foreach ($informalSale->products as $oldItem) {
                    $calc = $this->calculateBoxAndLayer($oldItem->product_id, $oldItem->quantity);
                    $this->increaseStock($oldItem->product_id, $oldItem->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            // حذف آیتم‌های قبلی
            $informalSale->products()->delete();

            // آپدیت فاکتور
            $informalSale->update([
                'year' => $year,
                'date' => $gregorianDate,
                'customer_id' => $customer->id,
                'customer_name' => $customerName,
                'total_price' => $totalPrice,
                'status' => $newStatus,
            ]);

            // آیتم‌های جدید + کسر موجودی
            foreach ($validated['items'] as $item) {
                InformalSaleProduct::create([
                    'informal_sale_id' => $informalSale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                // ✅ کسر موجودی (فقط اگه فاکتور لغو نشده باشه)
                if ($newStatus !== 'canceled') {
                    $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                    $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            DB::commit();

            return redirect()->route('informal-sales.index')
                ->with('success', 'فروش غیررسمی با موفقیت ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(InformalSale $informalSale)
    {
        DB::beginTransaction();

        try {
            // ✅ برگرداندن موجودی (فقط اگه فاکتور لغو نشده بود)
            if ($informalSale->status !== 'canceled') {
                foreach ($informalSale->products as $item) {
                    $calc = $this->calculateBoxAndLayer($item->product_id, $item->quantity);
                    $this->increaseStock($item->product_id, $item->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            $informalSale->products()->delete();
            $informalSale->delete();

            DB::commit();

            return redirect()->route('informal-sales.index')
                ->with('success', 'فروش غیررسمی حذف شد و موجودی برگردانده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف: ' . $e->getMessage()]);
        }
    }

    public function markAsPaid(InformalSale $informalSale)
    {
        $informalSale->update(['status' => 'paid']);
        return redirect()->route('informal-sales.index')
            ->with('success', 'وضعیت به پرداخت شده تغییر یافت.');
    }

    public function cancel(InformalSale $informalSale)
    {
        if ($informalSale->status === 'canceled') {
            return back()->with('error', 'این فاکتور قبلاً لغو شده است.');
        }

        DB::beginTransaction();

        try {
            // ✅ برگرداندن موجودی
            foreach ($informalSale->products as $item) {
                $calc = $this->calculateBoxAndLayer($item->product_id, $item->quantity);
                $this->increaseStock($item->product_id, $item->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
            }

            $informalSale->update(['status' => 'canceled']);

            DB::commit();

            return redirect()->route('informal-sales.index')
                ->with('success', 'فاکتور لغو شد و موجودی برگردانده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در لغو: ' . $e->getMessage()]);
        }
    }
}