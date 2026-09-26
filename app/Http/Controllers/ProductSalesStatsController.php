<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SaleProduct;
use App\Models\InformalSaleProduct;
use App\Models\CustomerPayment;
use App\Models\UserSalesStatProduct;
use App\Models\UserSalesStatCustomer;
use App\Models\UserSalesStatCustomerProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class ProductSalesStatsController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // ===== محصولات انتخابی =====
        $selectedProductIds = UserSalesStatProduct::where('user_id', $userId)
            ->orderBy('order')
            ->pluck('product_id')
            ->toArray();

        $selectedProducts = Product::whereIn('id', $selectedProductIds)
            ->where('status', 1)
            ->get();

        $allProductsList = Product::where('status', 1)->orderBy('name')->get();

        // ===== مشتری‌های انتخابی =====
        $selectedCustomerIds = UserSalesStatCustomer::where('user_id', $userId)
            ->orderBy('order')
            ->pluck('customer_id')
            ->toArray();

        $customerOrderMap = array_flip($selectedCustomerIds);

        $selectedCustomers = Customer::whereIn('id', $selectedCustomerIds)
            ->get()
            ->sortBy(fn($c) => $customerOrderMap[$c->id] ?? 999999)
            ->values();

        $allCustomersList = Customer::where('status', 1)->orderBy('name')->get();

        // ===== فیلتر ماه و سال =====
        $month = (int) $request->input('month', Jalalian::now()->getMonth());
        $year  = (int) $request->input('year', Jalalian::now()->getYear());

        // ✅ اگه month = 0 → همه ماه‌های سال
        if ($month === 0) {
            $startDateStr = sprintf('%04d/01/01', $year);
            $endDateStr   = sprintf('%04d/12/29', $year);

            try {
                $startDate = Jalalian::fromFormat('Y/m/d', $startDateStr)->toCarbon()->toDateString();
                $endDate   = Jalalian::fromFormat('Y/m/d', $endDateStr)->toCarbon()->toDateString();
            } catch (\Exception $e) {
                $startDate = null;
                $endDate = null;
            }
        } else {
            $startDateStr = sprintf('%04d/%02d/01', $year, $month);
            $lastDay = Jalalian::fromFormat('Y/m/d', $startDateStr)->getMonthDays();
            $endDateStr = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

            try {
                $startDate = Jalalian::fromFormat('Y/m/d', $startDateStr)->toCarbon()->toDateString();
                $endDate = Jalalian::fromFormat('Y/m/d', $endDateStr)->toCarbon()->toDateString();
            } catch (\Exception $e) {
                $startDate = null;
                $endDate = null;
            }
        }

        $reportData = collect();

        if ($selectedCustomers->count() > 0) {
            $customersToShow = $selectedCustomers;
        } else {
            $customersToShow = $allCustomersList;
        }

        foreach ($customersToShow as $customer) {
            $customerName = $customer->name;
            $productData = [];

            // ===== فروش رسمی =====
            $formalQuery = SaleProduct::with(['sale', 'product'])
                ->whereHas('sale', function ($q) use ($customerName, $startDate, $endDate) {
                    $q->where('customer_name', $customerName)
                      ->whereDate('date', '>=', $startDate)
                      ->whereDate('date', '<=', $endDate);
                });

            if (!empty($selectedProductIds)) {
                $formalQuery->whereIn('product_id', $selectedProductIds);
            }

            foreach ($formalQuery->get() as $item) {
                $pid = $item->product_id;
                $pName = $item->product->name ?? 'نامشخص';

                if (!isset($productData[$pid])) {
                    $productData[$pid] = [
                        'product_id'      => $pid,
                        'product_name'    => $pName,
                        'formal_qty'      => 0,
                        'formal_amount'   => 0,
                        'informal_qty'    => 0,
                        'informal_amount' => 0,
                    ];
                }

                $productData[$pid]['formal_qty']    += $item->quantity;
                $productData[$pid]['formal_amount'] += $item->quantity * $item->unit_price;
            }

            // ===== فروش غیررسمی =====
            $informalQuery = InformalSaleProduct::with(['informalSale', 'product'])
                ->whereHas('informalSale', function ($q) use ($customerName, $startDate, $endDate) {
                    $q->where('customer_name', $customerName)
                      ->whereDate('date', '>=', $startDate)
                      ->whereDate('date', '<=', $endDate);
                });

            if (!empty($selectedProductIds)) {
                $informalQuery->whereIn('product_id', $selectedProductIds);
            }

            foreach ($informalQuery->get() as $item) {
                $pid = $item->product_id;
                $pName = $item->product->name ?? 'نامشخص';

                if (!isset($productData[$pid])) {
                    $productData[$pid] = [
                        'product_id'      => $pid,
                        'product_name'    => $pName,
                        'formal_qty'      => 0,
                        'formal_amount'   => 0,
                        'informal_qty'    => 0,
                        'informal_amount' => 0,
                    ];
                }

                $productData[$pid]['informal_qty']    += $item->quantity;
                $productData[$pid]['informal_amount'] += $item->quantity * $item->unit_price;
            }

            if (empty($productData)) continue;

            $customerFormalQty = 0;
            $customerFormalAmount = 0;
            $customerInformalQty = 0;
            $customerInformalAmount = 0;
            $customerTotalQty = 0;
            $customerTotalAmount = 0;

            foreach ($productData as &$p) {
                $p['total_qty']    = $p['formal_qty'] + $p['informal_qty'];
                $p['total_amount'] = $p['formal_amount'] + $p['informal_amount'];

                $customerFormalQty      += $p['formal_qty'];
                $customerFormalAmount   += $p['formal_amount'];
                $customerInformalQty    += $p['informal_qty'];
                $customerInformalAmount += $p['informal_amount'];
                $customerTotalQty       += $p['total_qty'];
                $customerTotalAmount    += $p['total_amount'];
            }
            unset($p);

            uasort($productData, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

            $paidAmount = CustomerPayment::getTotalPaidForCustomer($customerName, $endDate);
            $remaining  = $customerTotalAmount - $paidAmount;

            $reportData->push((object) [
                'customer_id'           => $customer->id,
                'customer_name'         => $customerName,
                'products'              => $productData,
                'products_count'        => count($productData),
                'formal_qty'            => $customerFormalQty,
                'formal_amount'         => $customerFormalAmount,
                'informal_qty'          => $customerInformalQty,
                'informal_amount'       => $customerInformalAmount,
                'total_qty'             => $customerTotalQty,
                'total_amount'          => $customerTotalAmount,
                'paid_amount'           => $paidAmount,
                'remaining'             => $remaining,
            ]);
        }

        $reportData = $reportData->sortByDesc('total_amount')->values();

        return view('reports.product-sales-stats', [
            'selectedProducts'  => $selectedProducts,
            'allProductsList'   => $allProductsList,
            'selectedCustomers' => $selectedCustomers,
            'allCustomersList'  => $allCustomersList,
            'month'             => $month,
            'year'              => $year,
            'reportData'        => $reportData,
            'currentYear'       => Jalalian::now()->getYear(),
            'monthNames'        => ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        ]);
    }

    // ============================================================
    //  ✅ پارامترهای فیلتر که باید توی redirect حفظ بشن
    // ============================================================
    private function filterParams(Request $request): array
    {
        $params = [];
        if ($request->filled('year')) {
            $params['year'] = $request->input('year');
        }
        if ($request->filled('month')) {
            $params['month'] = $request->input('month');
        }
        return $params;
    }

    // ============================================================
    //  افزودن محصول دستی
    // ============================================================
    public function addProduct(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $userId = auth()->id();

        $exists = UserSalesStatProduct::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            $lastOrder = UserSalesStatProduct::where('user_id', $userId)->max('order') ?? 0;

            UserSalesStatProduct::create([
                'user_id'        => $userId,
                'product_id'     => $request->product_id,
                'order'          => $lastOrder + 1,
                'manually_added' => true,
            ]);

            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('success', 'محصول به لیست اضافه شد.');
        }

        return redirect()->route('product-sales-stats.index', $this->filterParams($request))
            ->with('info', 'این محصول قبلاً به لیست اضافه شده است.');
    }

    // ============================================================
    //  حذف محصول از لیست
    // ============================================================
    public function removeProduct(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $userId = auth()->id();
        $productId = (int) $request->product_id;

        DB::beginTransaction();
        try {
            UserSalesStatProduct::where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            DB::commit();

            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('success', 'محصول از لیست حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('error', 'خطا: ' . $e->getMessage());
        }
    }

    // ============================================================
    //  ترتیب سفارشی
    // ============================================================
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:products,id',
        ]);

        $userId = auth()->id();
        $order = $request->input('order');

        DB::beginTransaction();
        try {
            foreach ($order as $index => $productId) {
                UserSalesStatProduct::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->update(['order' => $index + 1]);
            }

            $allSelectedIds = UserSalesStatProduct::where('user_id', $userId)
                ->orderBy('order')->pluck('product_id')->toArray();
            $remaining = array_values(array_diff($allSelectedIds, $order));

            if (!empty($remaining)) {
                $startPos = count($order) + 1;
                $existingOrder = UserSalesStatProduct::where('user_id', $userId)
                    ->whereIn('product_id', $remaining)
                    ->orderBy('order')->pluck('product_id')->toArray();

                foreach ($existingOrder as $i => $productId) {
                    UserSalesStatProduct::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->update(['order' => $startPos + $i]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'ترتیب ذخیره شد.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ============================================================
    //  ✅ افزودن مشتری + محصولاتش خودکار
    // ============================================================
    public function addCustomer(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $userId = auth()->id();
        $customerId = (int) $request->customer_id;

        $exists = UserSalesStatCustomer::where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->exists();

        if ($exists) {
            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('info', 'این مشتری قبلاً به لیست اضافه شده است.');
        }

        DB::beginTransaction();
        try {
            $lastCustomerOrder = UserSalesStatCustomer::where('user_id', $userId)->max('order') ?? 0;

            UserSalesStatCustomer::create([
                'user_id'     => $userId,
                'customer_id' => $customerId,
                'order'       => $lastCustomerOrder + 1,
            ]);

            $customer = Customer::find($customerId);
            $customerName = $customer->name;

            $formalProductIds = SaleProduct::whereHas('sale', function ($q) use ($customerName) {
                $q->where('customer_name', $customerName);
            })->pluck('product_id')->unique()->toArray();

            $informalProductIds = InformalSaleProduct::whereHas('informalSale', function ($q) use ($customerName) {
                $q->where('customer_name', $customerName);
            })->pluck('product_id')->unique()->toArray();

            $allProductIds = array_unique(array_merge($formalProductIds, $informalProductIds));

            $validProductIds = Product::whereIn('id', $allProductIds)
                ->where('status', 1)
                ->pluck('id')
                ->toArray();

            foreach ($validProductIds as $productId) {
                UserSalesStatCustomerProduct::firstOrCreate([
                    'user_id'     => $userId,
                    'customer_id' => $customerId,
                    'product_id'  => $productId,
                ]);
            }

            $existingProductIds = UserSalesStatProduct::where('user_id', $userId)
                ->pluck('product_id')
                ->toArray();

            $newProductIds = array_diff($validProductIds, $existingProductIds);

            $addedCount = 0;
            if (!empty($newProductIds)) {
                $lastProductOrder = UserSalesStatProduct::where('user_id', $userId)->max('order') ?? 0;

                foreach ($newProductIds as $i => $productId) {
                    UserSalesStatProduct::create([
                        'user_id'        => $userId,
                        'product_id'     => $productId,
                        'order'          => $lastProductOrder + $i + 1,
                        'manually_added' => false,
                    ]);
                    $addedCount++;
                }
            }

            DB::commit();

            $message = 'مشتری به لیست اضافه شد.';
            if ($addedCount > 0) {
                $message .= " و {$addedCount} محصول از فروش‌های او اضافه شد.";
            }

            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('addCustomer failed: ' . $e->getMessage());
            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('error', 'خطا در افزودن مشتری: ' . $e->getMessage());
        }
    }

    // ============================================================
    //  ✅ حذف مشتری + محصولاتش
    // ============================================================
    public function removeCustomer(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $userId = auth()->id();
        $customerId = (int) $request->customer_id;

        DB::beginTransaction();
        try {
            UserSalesStatCustomer::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->delete();

            $customerProductIds = UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->pluck('product_id')
                ->toArray();

            UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->delete();

            $deletedCount = 0;

            foreach ($customerProductIds as $productId) {
                $hasOtherCustomer = UserSalesStatCustomerProduct::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();

                if ($hasOtherCustomer) continue;

                $existing = UserSalesStatProduct::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->first();

                if (!$existing) continue;
                if ($existing->manually_added) continue;

                $existing->delete();
                $deletedCount++;
            }

            DB::commit();

            $message = 'مشتری از لیست حذف شد.';
            if ($deletedCount > 0) {
                $message .= " و {$deletedCount} محصول مرتبط هم حذف شد.";
            }

            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('removeCustomer failed: ' . $e->getMessage());
            return redirect()->route('product-sales-stats.index', $this->filterParams($request))
                ->with('error', 'خطا در حذف مشتری: ' . $e->getMessage());
        }
    }
}