<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleProduct;
use App\Models\InformalSaleProduct;
use App\Models\UserSalesStatProduct;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ProductSalesStatsController extends Controller
{
    /**
     * نمایش صفحه آمار فروش با محصولات انتخابی کاربر
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // دریافت محصولات انتخابی کاربر برای این بخش
        $selectedProductIds = UserSalesStatProduct::where('user_id', $userId)
            ->orderBy('order')
            ->pluck('product_id')
            ->toArray();

        $selectedProducts = Product::whereIn('id', $selectedProductIds)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // لیست کامل محصولات برای جستجو
        $allProductsList = Product::where('status', 1)->orderBy('name')->get();

        // دریافت پارامترهای فیلتر (ماه و سال)
        $month = (int) $request->input('month', Jalalian::now()->getMonth());
        $year  = (int) $request->input('year', Jalalian::now()->getYear());

        $reportData = collect();

        if ($selectedProducts->count() > 0) {
            // تاریخ شمسی به صورت رشته
            $startDateStr = sprintf('%04d/%02d/01', $year, $month);
            $lastDay = Jalalian::fromFormat('Y/m/d', $startDateStr)->getMonthDays();
            $endDateStr = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

            // تبدیل به میلادی (فقط تاریخ، بدون زمان)
            try {
                $startDate = Jalalian::fromFormat('Y/m/d', $startDateStr)->toCarbon()->toDateString();
                $endDate = Jalalian::fromFormat('Y/m/d', $endDateStr)->toCarbon()->toDateString();
            } catch (\Exception $e) {
                $startDate = null;
                $endDate = null;
            }

            foreach ($selectedProducts as $product) {
                // ===== فروش رسمی =====
                $formalItems = SaleProduct::where('product_id', $product->id)
                    ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                        $q->whereDate('date', '>=', $startDate)
                          ->whereDate('date', '<=', $endDate);
                    })
                    ->get();

                $formalTotal = $formalItems->sum('quantity');
                $formalAmount = $formalItems->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                });

                // ===== فروش غیررسمی =====
                $informalItems = InformalSaleProduct::where('product_id', $product->id)
                    ->whereHas('informalSale', function ($q) use ($startDate, $endDate) {
                        $q->whereDate('date', '>=', $startDate)
                          ->whereDate('date', '<=', $endDate);
                    })
                    ->get();

                $informalTotal = $informalItems->sum('quantity');
                $informalAmount = $informalItems->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                });

                // ✅ اضافه شدن product_id برای استفاده در فرم حذف
                $reportData->push((object) [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'formal_total' => $formalTotal,
                    'formal_amount' => $formalAmount,
                    'informal_total' => $informalTotal,
                    'informal_amount' => $informalAmount,
                    'total_quantity' => $formalTotal + $informalTotal,
                    'total_amount' => $formalAmount + $informalAmount,
                ]);
            }

            $reportData = $reportData->sortBy('product_name')->values();
        }

        return view('reports.product-sales-stats', [
            'selectedProducts' => $selectedProducts,
            'allProductsList' => $allProductsList,
            'month' => $month,
            'year' => $year,
            'reportData' => $reportData,
            'currentYear' => Jalalian::now()->getYear(),
            'monthNames' => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        ]);
    }

    /**
     * افزودن محصول به لیست انتخابی کاربر
     */
    public function addProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $userId = auth()->id();

        $exists = UserSalesStatProduct::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            $lastOrder = UserSalesStatProduct::where('user_id', $userId)->max('order') ?? 0;

            UserSalesStatProduct::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
                'order' => $lastOrder + 1,
            ]);

            return redirect()->route('product-sales-stats.index')
                ->with('success', 'محصول با موفقیت به لیست آمار فروش اضافه شد.');
        }

        return redirect()->route('product-sales-stats.index')
            ->with('info', 'این محصول قبلاً به لیست شما اضافه شده است.');
    }

    /**
     * حذف محصول از لیست انتخابی کاربر
     */
    public function removeProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $userId = auth()->id();

        UserSalesStatProduct::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->delete();

        return redirect()->route('product-sales-stats.index')
            ->with('success', 'محصول با موفقیت از لیست آمار فروش حذف شد.');
    }
}