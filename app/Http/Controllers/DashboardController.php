<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Production;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;
use App\Models\UserDashboardItem;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // دریافت محصولات انتخابی کاربر
        $selectedProducts = UserDashboardItem::where('user_id', $user->id)
            ->with('product')
            ->get()
            ->pluck('product');

        $feedRateData = [];

        foreach ($selectedProducts as $product) {
            // فقط محصولاتی که خوراک پخت دارند
            if (!$product->tonneli_feed_rate || $product->tonneli_feed_rate <= 0) {
                continue;
            }

            // محاسبه موجودی خام
            $totalProduction = Production::where('product_id', $product->id)
                ->where('stage', 'production')
                ->sum('quantity');

            $tonneliConsumption = TonneliFiringItem::where('product_id', $product->id)->sum('input_quantity');
            $shuttleConsumption = ShuttleFiring::where('product_id', $product->id)->sum('output_quantity');
            $rawStock = max(0, $totalProduction - $tonneliConsumption - $shuttleConsumption);

            // محاسبه ساعت موجودی
            $feedRate = $product->tonneli_feed_rate;
            $hours = ($feedRate > 0 && $rawStock > 0) ? round($rawStock / $feedRate, 2) : 0;

            $feedRateData[] = [
                'id' => $product->id,
                'name' => $product->name,
                'raw_stock' => $rawStock,
                'hours' => $hours,
            ];
        }

        // لیست همه محصولات برای Select2
        $allProducts = Product::where('status', true)->orderBy('name')->get();

        return view('dashboard', compact('feedRateData', 'allProducts'));
    }

    public function addProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();

        $exists = UserDashboardItem::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$exists) {
            UserDashboardItem::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'محصول به داشبورد اضافه شد.');
    }

    public function removeProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();

        UserDashboardItem::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->delete();

        return redirect()->route('dashboard')->with('success', 'محصول از داشبورد حذف شد.');
    }
}