<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
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

    public function index(Request $request)
    {
        $source = $request->input('source', 'all');
        $status = $request->input('status', 'all');

        $query = Sale::orderBy('date', 'desc')->orderBy('id', 'desc');

        if ($source === 'manual') {
            $query->where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            });
        } elseif ($source === 'imported') {
            $query->where('is_imported', true);
        }

        if ($status !== 'all' && in_array($status, ['pending', 'paid', 'cancelled'])) {
            $query->where('status', $status);
        }

        $sales = $query->paginate(15)->appends($request->all());

        $statusCounts = [
            'all'       => Sale::count(),
            'pending'   => Sale::where('status', 'pending')->count(),
            'paid'      => Sale::where('status', 'paid')->count(),
            'cancelled' => Sale::where('status', 'cancelled')->count(),
        ];

        return view('sales.index', compact('sales', 'source', 'status', 'statusCounts'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $today = Jalalian::now()->format('Y/m/d');
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $defaultTax = $lastSale ? $lastSale->tax_percent : 9;

        return view('sales.create', compact('products', 'today', 'defaultTax'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ثبت فاکتور جدید
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'invoice_number' => 'required|integer|unique:sales,invoice_number',
            'customer_name' => 'required|string|max:255',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            $totalPrice = 0;
            foreach ($validated['products'] as $item) {
                $totalPrice += $item['quantity'] * $item['unit_price'];
            }
            $totalWithTax = $totalPrice + ($totalPrice * $validated['tax_percent'] / 100);

            $sale = Sale::create([
                'invoice_number' => $validated['invoice_number'],
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
                'tax_percent' => $validated['tax_percent'],
                'total_price' => $totalPrice,
                'total_with_tax' => $totalWithTax,
                'status' => 'pending',
                'is_imported' => false,
            ]);

            foreach ($validated['products'] as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
            }

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return redirect()->route('sales.index')->with('success', "فاکتور شماره {$validated['invoice_number']} با موفقیت ثبت شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return back()->withErrors(['error' => 'خطا در ثبت فاکتور: ' . $e->getMessage()]);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load('products.product');
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return redirect()->route('sales.index')->with('error', 'فاکتورهای پرداخت شده یا باطل شده قابل ویرایش نیستند.');
        }

        $products = Product::where('status', true)->get();
        $sale->load('products');
        $sale->jalali_date = Jalalian::fromCarbon($sale->date)->format('Y/m/d');

        return view('sales.edit', compact('sale', 'products'));
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ ویرایش فاکتور
    // ═══════════════════════════════════════════════════════════
    public function update(Request $request, Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return back()->with('error', 'فاکتورهای پرداخت شده یا باطل شده قابل ویرایش نیستند.');
        }

        $validated = $request->validate([
            'date' => 'required|string',
            'invoice_number' => 'required|integer|unique:sales,invoice_number,' . $sale->id,
            'customer_name' => 'required|string|max:255',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            foreach ($sale->products as $oldProduct) {
                $calc = $this->calculateBoxAndLayer($oldProduct->product_id, $oldProduct->quantity);
                $this->increaseStock($oldProduct->product_id, $oldProduct->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
            }

            $sale->products()->delete();

            $totalPrice = 0;
            foreach ($validated['products'] as $item) {
                $totalPrice += $item['quantity'] * $item['unit_price'];
            }
            $totalWithTax = $totalPrice + ($totalPrice * $validated['tax_percent'] / 100);

            $sale->update([
                'invoice_number' => $validated['invoice_number'],
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
                'tax_percent' => $validated['tax_percent'],
                'total_price' => $totalPrice,
                'total_with_tax' => $totalWithTax,
            ]);

            foreach ($validated['products'] as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer'], $calc['pallet']);
            }

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return redirect()->route('sales.index')->with('success', 'فاکتور با موفقیت ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return back()->withErrors(['error' => 'خطا در ویرایش فاکتور: ' . $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف فاکتور
    // ═══════════════════════════════════════════════════════════
    public function destroy(Sale $sale)
    {
        if ($sale->status === 'paid') {
            return back()->with('error', 'فاکتورهای پرداخت شده قابل حذف نیستند.');
        }

        if ($sale->status === 'pending') {
            $saleData = $sale->toArray();
            $productsData = $sale->products->map(function ($product) {
                return $product->toArray();
            })->toArray();

            session(['undo_record' => [
                'class' => get_class($sale),
                'data'  => $saleData,
                'products' => $productsData,
            ]]);
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            if ($sale->status === 'pending') {
                foreach ($sale->products as $product) {
                    $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                    $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            // ✅ اول product ها رو پاک کن، بعد خود sale
            $sale->products()->delete();
            $sale->delete();

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return redirect()->route('sales.index')->with('success', 'فاکتور با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return back()->withErrors(['error' => 'خطا در حذف فاکتور: ' . $e->getMessage()]);
        }
    }

    public function markAsPaid(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'فاکتور باطل شده قابل تغییر نیست.');
        }

        $sale->status = 'paid';
        $sale->save();

        return back()->with('success', 'وضعیت فاکتور به "پرداخت شده" تغییر کرد.');
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ باطل کردن فاکتور
    // ═══════════════════════════════════════════════════════════
    public function cancel(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'فاکتور قبلاً باطل شده است.');
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            if ($sale->status === 'pending') {
                foreach ($sale->products as $product) {
                    $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                    $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                }
            }

            $sale->status = 'cancelled';
            $sale->save();

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return back()->with('success', 'فاکتور با موفقیت باطل شد و موجودی برگردانده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return back()->withErrors(['error' => 'خطا در باطل کردن فاکتور: ' . $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف رکوردهای ایمپورتی
    // ═══════════════════════════════════════════════════════════
    public function clearImported()
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            $ids = Sale::where('is_imported', true)->pluck('id')->toArray();
            $count = count($ids);

            if ($count > 0) {
                SaleProduct::whereIn('sale_id', $ids)->delete();
                Sale::whereIn('id', $ids)->delete();
            }

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return redirect()->route('sales.index')
                ->with('success', "✅ {$count} فاکتور رسمی ایمپورتی (اکسل) پاک شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return redirect()->route('sales.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ حذف رکوردهای دستی
    // ═══════════════════════════════════════════════════════════
    public function clearManual()
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::beginTransaction();

        try {
            $records = Sale::where(function ($q) {
                $q->where('is_imported', false)->orWhereNull('is_imported');
            })->with('products')->get();

            $count = 0;
            foreach ($records as $sale) {
                if ($sale->status === 'pending') {
                    foreach ($sale->products as $product) {
                        $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                        $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                    }
                }

                $sale->products()->delete();
                $sale->delete();
                $count++;
            }

            DB::commit();
            DB::statement('PRAGMA foreign_keys = ON');

            return redirect()->route('sales.index')
                ->with('success', "✅ {$count} فاکتور رسمی دستی پاک شد و موجودی اصلاح شد.");
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('PRAGMA foreign_keys = ON');
            return redirect()->route('sales.index')
                ->with('error', 'خطا در حذف: ' . $e->getMessage());
        }
    }
}