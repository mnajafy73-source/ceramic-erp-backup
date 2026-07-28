<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    private function calculateBoxAndLayer($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product || !$product->per_box || $product->per_box == 0) {
            return ['box' => 0, 'layer' => 0];
        }

        $box = intval($quantity / $product->per_box);
        $layer = 0;
        if ($product->layers_per_box && $product->layers_per_box > 0) {
            $perLayer = $product->per_box * $product->layers_per_box;
            $layer = intval($quantity / $perLayer);
        }

        return ['box' => $box, 'layer' => $layer];
    }

    private function decreaseStock($productId, $quantity, $box, $layer)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock = max(0, $product->initial_stock - $quantity);
        $product->save();
    }

    private function increaseStock($productId, $quantity, $box, $layer)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock += $quantity;
        $product->save();
    }

    private function syncInvoiceWithSale($invoiceId, $saleId)
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) return;

        if ($invoice->status === 'closed') {
            return;
        }

        $invoiceProducts = $invoice->products()->with('product')->get();
        $saleProducts = SaleProduct::where('sale_id', $saleId)->get();

        $isSame = true;
        if ($invoiceProducts->count() != $saleProducts->count()) {
            $isSame = false;
        } else {
            foreach ($invoiceProducts as $invProd) {
                $match = $saleProducts->firstWhere('product_id', $invProd->product_id);
                if (!$match || $match->quantity != $invProd->quantity) {
                    $isSame = false;
                    break;
                }
            }
        }

        DB::beginTransaction();
        try {
            if ($isSame) {
                $invoice->status = 'closed';
                $invoice->save();
            } else {
                foreach ($invoiceProducts as $invProd) {
                    $calc = $this->calculateBoxAndLayer($invProd->product_id, $invProd->quantity);
                    $this->increaseStock($invProd->product_id, $invProd->quantity, $calc['box'], $calc['layer']);
                }

                $invoice->products()->delete();

                foreach ($saleProducts as $saleProd) {
                    InvoiceProduct::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $saleProd->product_id,
                        'quantity' => $saleProd->quantity,
                        'box' => 0,
                        'layer' => 0,
                    ]);

                    $calc = $this->calculateBoxAndLayer($saleProd->product_id, $saleProd->quantity);
                    $this->decreaseStock($saleProd->product_id, $saleProd->quantity, $calc['box'], $calc['layer']);
                }

                $invoice->status = 'closed';
                $invoice->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function index()
    {
        $sales = Sale::orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('status', true)->get();
        $today = Jalalian::now()->format('Y/m/d');
        $invoices = Invoice::where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('number', 'desc')
            ->get();

        $lastSale = Sale::orderBy('id', 'desc')->first();
        $defaultTax = $lastSale ? $lastSale->tax_percent : 9;

        return view('sales.create', compact('products', 'today', 'invoices', 'defaultTax'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'invoice_number' => 'required|integer|unique:sales,invoice_number',
            'customer_name' => 'required|string|max:255',
            'invoice_id' => 'nullable|exists:invoices,id',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

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
                'invoice_id' => $validated['invoice_id'] ?? null,
                'tax_percent' => $validated['tax_percent'],
                'total_price' => $totalPrice,
                'total_with_tax' => $totalWithTax,
                'status' => 'pending',
            ]);

            foreach ($validated['products'] as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);

                if (empty($validated['invoice_id'])) {
                    $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                    $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer']);
                }
            }

            if (!empty($validated['invoice_id'])) {
                $this->syncInvoiceWithSale($validated['invoice_id'], $sale->id);
            }

            DB::commit();
            return redirect()->route('sales.index')->with('success', "فاکتور شماره {$validated['invoice_number']} با موفقیت ثبت شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت فاکتور: ' . $e->getMessage()]);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load('products.product', 'invoice');
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
        $invoices = Invoice::where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('number', 'desc')
            ->get();

        return view('sales.edit', compact('sale', 'products', 'invoices'));
    }

    public function update(Request $request, Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return back()->with('error', 'فاکتورهای پرداخت شده یا باطل شده قابل ویرایش نیستند.');
        }

        $validated = $request->validate([
            'date' => 'required|string',
            'invoice_number' => 'required|integer|unique:sales,invoice_number,' . $sale->id,
            'customer_name' => 'required|string|max:255',
            'invoice_id' => 'nullable|exists:invoices,id',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            if (empty($sale->invoice_id)) {
                foreach ($sale->products as $oldProduct) {
                    $calc = $this->calculateBoxAndLayer($oldProduct->product_id, $oldProduct->quantity);
                    $this->increaseStock($oldProduct->product_id, $oldProduct->quantity, $calc['box'], $calc['layer']);
                }
            }

            if (!empty($sale->invoice_id)) {
                $oldInvoice = Invoice::find($sale->invoice_id);
                if ($oldInvoice && $oldInvoice->status === 'closed') {
                    $oldInvoice->status = 'open';
                    $oldInvoice->save();
                }
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
                'invoice_id' => $validated['invoice_id'] ?? null,
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

                if (empty($validated['invoice_id'])) {
                    $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);
                    $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer']);
                }
            }

            if (!empty($validated['invoice_id'])) {
                $this->syncInvoiceWithSale($validated['invoice_id'], $sale->id);
            }

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'فاکتور با موفقیت ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش فاکتور: ' . $e->getMessage()]);
        }
    }

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

        DB::beginTransaction();

        try {
            if ($sale->status === 'pending') {
                if (empty($sale->invoice_id)) {
                    foreach ($sale->products as $product) {
                        $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                        $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer']);
                    }
                }

                if (!empty($sale->invoice_id)) {
                    $invoice = Invoice::find($sale->invoice_id);
                    if ($invoice && $invoice->status === 'closed') {
                        $invoice->status = 'open';
                        $invoice->save();
                    }
                }
            }

            $sale->delete();
            DB::commit();

            return redirect()->route('sales.index')->with('success', 'فاکتور با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
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

    public function cancel(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'فاکتور قبلاً باطل شده است.');
        }

        DB::beginTransaction();

        try {
            if ($sale->status === 'pending') {
                if (empty($sale->invoice_id)) {
                    foreach ($sale->products as $product) {
                        $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                        $this->increaseStock($product->product_id, $product->quantity, $calc['box'], $calc['layer']);
                    }
                }

                if (!empty($sale->invoice_id)) {
                    $invoice = Invoice::find($sale->invoice_id);
                    if ($invoice && $invoice->status === 'closed') {
                        $invoice->status = 'open';
                        $invoice->save();
                    }
                }
            }

            $sale->status = 'cancelled';
            $sale->save();

            DB::commit();
            return back()->with('success', 'فاکتور با موفقیت باطل شد و موجودی برگردانده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در باطل کردن فاکتور: ' . $e->getMessage()]);
        }
    }

    public function getInvoiceProducts(Invoice $invoice)
    {
        $products = $invoice->products()->with('product')->get();
        $data = [];
        foreach ($products as $item) {
            $data[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? '—',
                'quantity' => $item->quantity,
                'unit_price' => 0,
            ];
        }
        return response()->json($data);
    }
}