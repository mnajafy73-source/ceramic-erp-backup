<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\SaleProduct;
use App\Models\InformalSaleProduct;
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

        $orderMap = array_flip($selectedProductIds);

        $selectedProducts = Product::whereIn('id', $selectedProductIds)
            ->where('status', 1)
            ->get()
            ->sortBy(fn($p) => $orderMap[$p->id] ?? 999999)
            ->values();

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

        $selectedCustomerNames = $selectedCustomers->pluck('name')->toArray();

        // ===== فیلتر ماه و سال =====
        $month = (int) $request->input('month', Jalalian::now()->getMonth());
        $year  = (int) $request->input('year', Jalalian::now()->getYear());

        $reportData = collect();

        if ($selectedProducts->count() > 0) {
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

            foreach ($selectedProducts as $product) {
                $customerData = [];

                // فروش رسمی
                $formalItems = SaleProduct::with('sale')
                    ->where('product_id', $product->id)
                    ->whereHas('sale', function ($q) use ($startDate, $endDate, $selectedCustomerNames) {
                        $q->whereDate('date', '>=', $startDate)
                          ->whereDate('date', '<=', $endDate);

                        if (!empty($selectedCustomerNames)) {
                            $q->whereIn('customer_name', $selectedCustomerNames);
                        }
                    })
                    ->get();

                foreach ($formalItems as $item) {
                    $customerName = trim($item->sale->customer_name ?? '');
                    if ($customerName === '') $customerName = 'نامشخص';

                    if (!isset($customerData[$customerName])) {
                        $customerData[$customerName] = [
                            'name' => $customerName,
                            'formal_total' => 0, 'formal_amount' => 0,
                            'informal_total' => 0, 'informal_amount' => 0,
                        ];
                    }
                    $customerData[$customerName]['formal_total'] += $item->quantity;
                    $customerData[$customerName]['formal_amount'] += $item->quantity * $item->unit_price;
                }

                // فروش غیررسمی
                $informalItems = InformalSaleProduct::with('informalSale')
                    ->where('product_id', $product->id)
                    ->whereHas('informalSale', function ($q) use ($startDate, $endDate, $selectedCustomerNames) {
                        $q->whereDate('date', '>=', $startDate)
                          ->whereDate('date', '<=', $endDate);

                        if (!empty($selectedCustomerNames)) {
                            $q->whereIn('customer_name', $selectedCustomerNames);
                        }
                    })
                    ->get();

                foreach ($informalItems as $item) {
                    $customerName = trim($item->informalSale->customer_name ?? '');
                    if ($customerName === '') $customerName = 'نامشخص';

                    if (!isset($customerData[$customerName])) {
                        $customerData[$customerName] = [
                            'name' => $customerName,
                            'formal_total' => 0, 'formal_amount' => 0,
                            'informal_total' => 0, 'informal_amount' => 0,
                        ];
                    }
                    $customerData[$customerName]['informal_total'] += $item->quantity;
                    $customerData[$customerName]['informal_amount'] += $item->quantity * $item->unit_price;
                }

                $productTotalQty = 0; $productTotalAmount = 0;
                $productFormalTotal = 0; $productFormalAmount = 0;
                $productInformalTotal = 0; $productInformalAmount = 0;

                foreach ($customerData as $key => $data) {
                    $data['total_quantity'] = $data['formal_total'] + $data['informal_total'];
                    $data['total_amount'] = $data['formal_amount'] + $data['informal_amount'];

                    if ($data['total_quantity'] <= 0) {
                        unset($customerData[$key]);
                        continue;
                    }

                    $customerData[$key] = $data;
                    $productTotalQty += $data['total_quantity'];
                    $productTotalAmount += $data['total_amount'];
                    $productFormalTotal += $data['formal_total'];
                    $productFormalAmount += $data['formal_amount'];
                    $productInformalTotal += $data['informal_total'];
                    $productInformalAmount += $data['informal_amount'];
                }

                if (empty($customerData)) continue;

                uasort($customerData, fn($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);

                $reportData->push((object) [
                    'product_id'            => $product->id,
                    'product_name'          => $product->name,
                    'customers'             => $customerData,
                    'customers_count'       => count($customerData),
                    'formal_total'          => $productFormalTotal,
                    'formal_amount'         => $productFormalAmount,
                    'informal_total'        => $productInformalTotal,
                    'informal_amount'       => $productInformalAmount,
                    'total_quantity'        => $productTotalQty,
                    'total_amount'          => $productTotalAmount,
                ]);
            }
        }

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
                'manually_added' => true,    // ✅ دستی
            ]);

            return redirect()->route('product-sales-stats.index')
                ->with('success', 'محصول به لیست اضافه شد.');
        }

        return redirect()->route('product-sales-stats.index')
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
            // حذف از لیست کاربر
            UserSalesStatProduct::where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            // حذف از pivot همه مشتری‌ها
            UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            DB::commit();

            return redirect()->route('product-sales-stats.index')
                ->with('success', 'محصول از لیست حذف شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('product-sales-stats.index')
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
            return redirect()->route('product-sales-stats.index')
                ->with('info', 'این مشتری قبلاً به لیست اضافه شده است.');
        }

        DB::beginTransaction();
        try {
            // ۱. ثبت مشتری
            $lastCustomerOrder = UserSalesStatCustomer::where('user_id', $userId)->max('order') ?? 0;

            UserSalesStatCustomer::create([
                'user_id'     => $userId,
                'customer_id' => $customerId,
                'order'       => $lastCustomerOrder + 1,
            ]);

            // ۲. پیدا کردن محصولات این مشتری
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

            // ۳. ثبت در pivot (که این مشتری این محصولات رو داره)
            foreach ($validProductIds as $productId) {
                UserSalesStatCustomerProduct::firstOrCreate([
                    'user_id'     => $userId,
                    'customer_id' => $customerId,
                    'product_id'  => $productId,
                ]);
            }

            // ۴. اضافه کردن محصولات جدید به لیست کاربر
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
                        'manually_added' => false,    // ✅ خودکار
                    ]);
                    $addedCount++;
                }
            }

            DB::commit();

            $message = 'مشتری به لیست اضافه شد.';
            if ($addedCount > 0) {
                $message .= " و {$addedCount} محصول از فروش‌های او اضافه شد.";
            }

            return redirect()->route('product-sales-stats.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('addCustomer failed: ' . $e->getMessage());
            return redirect()->route('product-sales-stats.index')
                ->with('error', 'خطا در افزودن مشتری: ' . $e->getMessage());
        }
    }

    // ============================================================
    //  ✅ حذف مشتری + محصولاتش (اگه کسی دیگه‌ای نداره و دستی نبود)
    // ============================================================
    public function removeCustomer(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);

        $userId = auth()->id();
        $customerId = (int) $request->customer_id;

        DB::beginTransaction();
        try {
            // ۱. حذف مشتری از لیست
            UserSalesStatCustomer::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->delete();

            // ۲. پیدا کردن محصولاتی که این مشتری داشت
            $customerProductIds = UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->pluck('product_id')
                ->toArray();

            // ۳. حذف رکوردهای pivot این مشتری
            UserSalesStatCustomerProduct::where('user_id', $userId)
                ->where('customer_id', $customerId)
                ->delete();

            // ۴. برای هر محصول، بررسی کن که حذف بشه یا بمونه
            $deletedCount = 0;

            foreach ($customerProductIds as $productId) {
                // آیا مشتری دیگه‌ای هم این محصول رو داره؟
                $hasOtherCustomer = UserSalesStatCustomerProduct::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();

                if ($hasOtherCustomer) {
                    continue;    // بمونه
                }

                // آیا کاربر دستی این محصول رو اضافه کرده؟
                $existing = UserSalesStatProduct::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->first();

                if (!$existing) {
                    continue;    // نبود، کاری نیست
                }

                if ($existing->manually_added) {
                    continue;    // کاربر دستی اضافه کرده، بمونه
                }

                // حذف
                $existing->delete();
                $deletedCount++;
            }

            DB::commit();

            $message = 'مشتری از لیست حذف شد.';
            if ($deletedCount > 0) {
                $message .= " و {$deletedCount} محصول مرتبط هم حذف شد.";
            }

            return redirect()->route('product-sales-stats.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('removeCustomer failed: ' . $e->getMessage());
            return redirect()->route('product-sales-stats.index')
                ->with('error', 'خطا در حذف مشتری: ' . $e->getMessage());
        }
    }
}