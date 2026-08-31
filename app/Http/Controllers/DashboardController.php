<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\DashboardProduct;
use App\Models\ShuttleFiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // دریافت محصولات انتخابی کاربر
        $selectedProductIds = DashboardProduct::where('user_id', $userId)
            ->orderBy('order')
            ->pluck('product_id')
            ->toArray();

        $allProducts = Product::whereIn('id', $selectedProductIds)
            ->where('status', 1)
            ->orderBy('name', 'asc')
            ->get();

        foreach ($allProducts as $product) {
            // ✅ موجودی خام دقیقاً از همان متد موجودی خام (منوی موجودی خام)
            $stock = ShuttleFiring::getRawStock($product->id);

            // جمع تولیدات (برای نمایش در کارت)
            $productionSum = Production::where('product_id', $product->id)
                ->where('stage', 'production')
                ->sum('quantity');

            // جمع تونلی (برای نمایش)
            $tonneliSum = 0;
            if (Schema::hasTable('tonneli_firing_items')) {
                $tonneliSum = DB::table('tonneli_firing_items')
                    ->where('product_id', $product->id)
                    ->sum('input_quantity') ?? 0;
            }

            // جمع شاتل (برای نمایش)
            $shuttleSum = 0;
            if (Schema::hasTable('shuttle_firings')) {
                $shuttleSum = DB::table('shuttle_firings')
                    ->where('product_id', $product->id)
                    ->sum('output_quantity') ?? 0;
            }

            // محاسبه زمان پخت تونلی بر اساس موجودی خام و خوراک
            $tonneliTime = null;
            if ($product->tonneli_feed_rate && $product->tonneli_feed_rate > 0 && $stock > 0) {
                $tonneliTime = round($stock / $product->tonneli_feed_rate, 1);
            }

            // اختصاص به آبجکت محصول
            $product->production_sum = $productionSum;
            $product->tonneli_sum = $tonneliSum;
            $product->shuttle_sum = $shuttleSum;
            $product->stock = $stock; // ✅ موجودی خام هماهنگ با منوی موجودی خام
            $product->tonneli_time = $tonneliTime;
        }

        // لیست کامل محصولات برای کشوی انتخاب
        $allProductsList = Product::where('status', 1)->orderBy('name')->get();

        return view('dashboard', compact('allProducts', 'allProductsList'));
    }

    public function addProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $userId = auth()->id();

        $exists = DashboardProduct::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            $lastOrder = DashboardProduct::where('user_id', $userId)->max('order') ?? 0;

            DashboardProduct::create([
                'user_id' => $userId,
                'product_id' => $request->product_id,
                'order' => $lastOrder + 1,
            ]);

            return redirect()->route('dashboard')->with('success', 'محصول با موفقیت به داشبورد اضافه شد.');
        }

        return redirect()->route('dashboard')->with('info', 'این محصول قبلاً به داشبورد شما اضافه شده است.');
    }

    public function removeProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $userId = auth()->id();

        DashboardProduct::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->delete();

        return redirect()->route('dashboard')->with('success', 'محصول با موفقیت از داشبورد حذف شد.');
    }
}