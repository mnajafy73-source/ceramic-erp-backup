<?php

namespace App\Http\Controllers;

use App\Models\InformalSale;
use App\Models\InformalSaleProduct;
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
        $sales = InformalSale::orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('informal-sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $today = Jalalian::now()->format('Y/m/d');
        $year = Jalalian::now()->getYear();
        $maxNumber = InformalSale::where('year', $year)->max('number');
        $nextNumber = $maxNumber ? $maxNumber + 1 : 1;
        $displayNumber = $year . '-' . $nextNumber;

        return view('informal-sales.create', compact('products', 'today', 'year', 'nextNumber', 'displayNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        $year = Jalalian::fromFormat('Y/m/d', $validated['date'])->getYear();
        $maxNumber = InformalSale::where('year', $year)->max('number');
        $number = $maxNumber ? $maxNumber + 1 : 1;

        DB::beginTransaction();

        try {
            $totalPrice = 0;
            foreach ($validated['products'] as $item) {
                $totalPrice += $item['quantity'] * $item['unit_price'];
            }

            $sale = InformalSale::create([
                'year' => $year,
                'number' => $number,
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($validated['products'] as $item) {
                InformalSaleProduct::create([
                    'informal_sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
            }

            DB::commit();
            return redirect()->route('informal-sales.index')->with('success', "فاکتور غیررسمی شماره {$year}-{$number} با موفقیت ثبت شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت فاکتور: ' . $e->getMessage()]);
        }
    }

    public function show(InformalSale $informal_sale)
    {
        $informal_sale->load('products.product');
        return view('informal-sales.show', compact('informal_sale'));
    }

    public function edit(InformalSale $informal_sale)
    {
        if ($informal_sale->status !== 'pending') {
            return redirect()->route('informal-sales.index')->with('error', 'فاکتورهای پرداخت شده یا باطل شده قابل ویرایش نیستند.');
        }

        $products = Product::where('status', true)->get();
        $informal_sale->load('products');
        $informal_sale->jalali_date = Jalalian::fromCarbon($informal_sale->date)->format('Y/m/d');

        return view('informal-sales.edit', compact('informal_sale', 'products'));
    }

    public function update(Request $request, InformalSale $informal_sale)
    {
        if ($informal_sale->status !== 'pending') {
            return back()->with('error', 'فاکتورهای پرداخت شده یا باطل شده قابل ویرایش نیستند.');
        }

        $validated = $request->validate([
            'date' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // برگرداندن موجودی قبلی
            foreach ($informal_sale->products as $oldProduct) {
                $calc = $this->calculateBoxAndLayer($oldProduct->product_id, $oldProduct->quantity);
                $this->increaseStock($oldProduct->product_id, $oldProduct->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
            }

            $informal_sale->products()->delete();

            $totalPrice = 0;
            foreach ($validated['products'] as $item) {
                $totalPrice += $item['quantity'] * $item['unit_price'];
            }

            $informal_sale->update([
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
                'total_price' => $totalPrice,
            ]);

            foreach ($validated['products'] as $item) {
                InformalSaleProduct::create([
                    'informal_sale_id' => $informal_sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
            }

            DB::commit();
            return redirect()->route('informal-sales.index')->with('success', 'فاکتور با موفقیت ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش فاکتور: ' . $e->getMessage()]);
        }
    }

    public function destroy(InformalSale $informal_sale)
    {
        if ($informal_sale->status === 'paid') {
            return back()->with('error', 'فاکتورهای پرداخت شده قابل حذف نیستند.');
        }

        if ($informal_sale->status === 'pending') {
            $saleData = $informal_sale->toArray();
            $productsData = $informal_sale->products->map(function ($product) {
                return $product->toArray();
            })->toArray();

            session(['undo_record' => [
                'class' => get_class($informal_sale),
                'data'  => $saleData,
                'products' => $productsData,
            ]]);
        }

        DB::beginTransaction();

        try {
            if ($informal_sale->status === 'pending') {
                foreach ($informal_sale->products as $product) {
                    $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                    $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            $informal_sale->delete();
            DB::commit();

            return redirect()->route('informal-sales.index')->with('success', 'فاکتور با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف فاکتور: ' . $e->getMessage()]);
        }
    }

    public function markAsPaid(InformalSale $informal_sale)
    {
        if ($informal_sale->status === 'cancelled') {
            return back()->with('error', 'فاکتور باطل شده قابل تغییر نیست.');
        }

        $informal_sale->status = 'paid';
        $informal_sale->save();

        return back()->with('success', 'وضعیت فاکتور به "پرداخت شده" تغییر کرد.');
    }

    public function cancel(InformalSale $informal_sale)
    {
        if ($informal_sale->status === 'cancelled') {
            return back()->with('error', 'فاکتور قبلاً باطل شده است.');
        }

        DB::beginTransaction();

        try {
            if ($informal_sale->status === 'pending') {
                foreach ($informal_sale->products as $product) {
                    $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                    $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            $informal_sale->status = 'cancelled';
            $informal_sale->save();

            DB::commit();
            return back()->with('success', 'فاکتور با موفقیت باطل شد و موجودی برگردانده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در باطل کردن فاکتور: ' . $e->getMessage()]);
        }
    }
}