<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * محاسبه کارتن و لایه بر اساس تعداد و اطلاعات محصول
     */
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

    /**
     * کاهش موجودی انبار
     */
    private function decreaseStock($productId, $quantity, $box, $layer)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock = max(0, $product->initial_stock - $quantity);
        $product->save();
    }

    /**
     * افزایش موجودی انبار (برای برگرداندن)
     */
    private function increaseStock($productId, $quantity, $box, $layer)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock += $quantity;
        $product->save();
    }

    /**
     * نمایش لیست حواله‌های باز
     */
    public function index()
    {
        $invoices = Invoice::where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('number', 'desc')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * نمایش فرم ثبت حواله جدید
     */
    public function create()
    {
        $products = Product::where('status', true)->get();
        $today = Jalalian::now()->format('Y/m/d');
        $year = Jalalian::now()->getYear();
        $maxNumber = Invoice::where('year', $year)->max('number');
        $nextNumber = $maxNumber ? $maxNumber + 1 : 1;
        $displayNumber = $year . '-' . $nextNumber;

        return view('invoices.create', compact('products', 'today', 'year', 'nextNumber', 'displayNumber'));
    }

    /**
     * ذخیره حواله جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $year = Jalalian::fromFormat('Y/m/d', $validated['date'])->getYear();
        $maxNumber = Invoice::where('year', $year)->max('number');
        $number = $maxNumber ? $maxNumber + 1 : 1;

        DB::beginTransaction();

        try {
            $invoice = Invoice::create([
                'year' => $year,
                'number' => $number,
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
                'status' => 'open',
            ]);

            foreach ($validated['products'] as $item) {
                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);

                InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'box' => $calc['box'],
                    'layer' => $calc['layer'],
                ]);

                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer']);
            }

            DB::commit();
            return redirect()->route('invoices.index')->with('success', "حواله شماره {$year}-{$number} با موفقیت ثبت شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ثبت حواله: ' . $e->getMessage()]);
        }
    }

    /**
     * نمایش جزئیات یک حواله
     */
    public function show(Invoice $invoice)
    {
        $invoice->load('products.product');
        return view('invoices.show', compact('invoice'));
    }

    /**
     * نمایش فرم ویرایش حواله (فقط حواله‌های باز)
     */
    public function edit(Invoice $invoice)
    {
        if ($invoice->status === 'closed') {
            return redirect()->route('invoices.index')->with('error', 'حواله بسته شده و قابل ویرایش نیست.');
        }

        $products = Product::where('status', true)->get();
        $invoice->load('products');
        $invoice->jalali_date = Jalalian::fromCarbon($invoice->date)->format('Y/m/d');

        return view('invoices.edit', compact('invoice', 'products'));
    }

    /**
     * بروزرسانی حواله (فقط حواله‌های باز)
     */
    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'closed') {
            return back()->with('error', 'حواله بسته شده و قابل ویرایش نیست.');
        }

        $validated = $request->validate([
            'date' => 'required|string',
            'customer_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            // برگرداندن موجودی قبلی
            foreach ($invoice->products as $oldProduct) {
                $this->increaseStock($oldProduct->product_id, $oldProduct->quantity, $oldProduct->box, $oldProduct->layer);
            }

            $invoice->products()->delete();

            $invoice->update([
                'date' => Jalalian::fromFormat('Y/m/d', $validated['date'])->toCarbon()->format('Y-m-d'),
                'customer_name' => $validated['customer_name'],
            ]);

            // ذخیره محصولات جدید و کاهش موجودی
            foreach ($validated['products'] as $item) {
                $calc = $this->calculateBoxAndLayer($item['product_id'], $item['quantity']);

                InvoiceProduct::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'box' => $calc['box'],
                    'layer' => $calc['layer'],
                ]);

                $this->decreaseStock($item['product_id'], $item['quantity'], $calc['box'], $calc['layer']);
            }

            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'حواله با موفقیت ویرایش شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ویرایش حواله: ' . $e->getMessage()]);
        }
    }

    /**
     * حذف حواله (فقط حواله‌های باز) – با قابلیت برگرداندن
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'closed') {
            return back()->with('error', 'حواله بسته شده و قابل حذف نیست.');
        }

        // ذخیره اطلاعات حواله و محصولات برای برگرداندن (Undo)
        $invoiceData = $invoice->toArray();
        $productsData = $invoice->products->map(function ($product) {
            return $product->toArray();
        })->toArray();

        session(['undo_record' => [
            'class' => get_class($invoice),
            'data'  => $invoiceData,
            'products' => $productsData, // محصولات نیز ذخیره می‌شوند
        ]]);

        DB::beginTransaction();

        try {
            // برگرداندن موجودی به حالت قبل
            foreach ($invoice->products as $product) {
                $this->increaseStock($product->product_id, $product->quantity, $product->box, $product->layer);
            }

            $invoice->delete();
            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'حواله با موفقیت حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در حذف حواله: ' . $e->getMessage()]);
        }
    }

    /**
     * بستن حواله (توسط فروش) – فقط وضعیت را تغییر می‌دهد، موجودی تغییری نمی‌کند
     */
    public function close(Invoice $invoice)
    {
        if ($invoice->status === 'closed') {
            return back()->with('error', 'حواله قبلاً بسته شده است.');
        }

        $invoice->status = 'closed';
        $invoice->save();

        return back()->with('success', 'حواله با موفقیت بسته شد.');
    }
}