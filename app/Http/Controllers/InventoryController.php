<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShuttleFiring;
use App\Models\Packaging;
use App\Models\RawMaterial;
use App\Models\WaxInventory;
use App\Models\Glaze1300Inventory;
use App\Models\WarehouseInventory;
use App\Models\ShoulderInventory;
use App\Models\WasteMumInventory;
use App\Models\OpeningInventory;
use App\Models\RawInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function rawMaterialsStock()
    {
        $materials = RawMaterial::orderBy('name')->get();
        return view('inventory.raw-materials', compact('materials'));
    }

    public function raw(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereNull('parent_product_id');

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $raw = RawInventory::where('product_id', $product->id)->value('stock') ?? 0;

            $inventories[] = [
                'product' => $product,
                'stock' => $raw,
            ];
        }

        return view('inventory.raw', compact('inventories'));
    }

    public function mum(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereHas('waxInventory', function ($q) {
                $q->where('stock', '>', 0);
            });

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => $product->waxInventory->stock ?? 0,
            ];
        }

        return view('inventory.mum', compact('inventories'));
    }

    public function glaze1300(Request $request)
    {
        $query = Product::where('status', 1)
            ->whereHas('glaze1300Inventory', function ($q) {
                $q->where('stock', '>', 0);
            });

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $inventories[] = [
                'product' => $product,
                'stock' => $product->glaze1300Inventory->stock ?? 0,
            ];
        }

        return view('inventory.glaze1300', compact('inventories'));
    }

    public function packagingStock()
    {
        $packagings = Packaging::orderBy('type')->orderBy('name')->get();
        return view('inventory.packaging-stock', compact('packagings'));
    }

    /**
     * محاسبه موجودی کل یک محصول با احتساب فرزندان
     * برای والدها: موجودی خودش + موجودی همه فرزندان
     * برای بقیه: موجودی خودش
     */
    private function calculateTotalWarehouseStock(Product $product)
    {
        // موجودی خود محصول
        $warehouse = WarehouseInventory::where('product_id', $product->id)->first();
        $total = $warehouse ? (float) $warehouse->stock : 0;

        // اضافه کردن موجودی فرزندان
        $children = Product::where('parent_product_id', $product->id)
            ->where('status', 1)
            ->get();

        foreach ($children as $child) {
            $childWarehouse = WarehouseInventory::where('product_id', $child->id)->first();
            if ($childWarehouse) {
                $total += (float) $childWarehouse->stock;
            }
        }

        return $total;
    }

    /**
     * موجودی انبار (با نمایش کارتن، بسته، پالت و ترتیب سفارشی)
     */
    public function warehouse(Request $request)
    {
        $showHidden = $request->input('show_hidden') == '1';

        $query = Product::where('status', 1);

        if (!$showHidden) {
            $query->where(function ($q) {
                $q->where('hidden_from_warehouse', false)
                  ->orWhereNull('hidden_from_warehouse');
            });
        }

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query
            ->orderByRaw('CASE WHEN warehouse_sort_order > 0 THEN warehouse_sort_order ELSE 999999 END ASC')
            ->orderBy('name')
            ->get();

        $inventories = [];

        foreach ($products as $product) {
            // ✅ موجودی = خودش + فرزندانش
            $stock = $this->calculateTotalWarehouseStock($product);

            $cartons = 0;
            $packs   = 0;
            $pallets = 0;

            if ($product->per_box && $product->per_box > 0) {
                $cartons = intval($stock / $product->per_box);
            }
            if ($product->per_pack && $product->per_pack > 0) {
                $packs = intval($stock / $product->per_pack);
            }
            if ($product->per_pallet && $product->per_pallet > 0) {
                $pallets = intval($stock / $product->per_pallet);
            }

            $inventories[] = [
                'product'    => $product,
                'stock'      => $stock,
                'cartons'    => $cartons,
                'packs'      => $packs,
                'pallets'    => $pallets,
                'per_box'    => $product->per_box,
                'per_pack'   => $product->per_pack,
                'per_pallet' => $product->per_pallet,
            ];
        }

        return view('inventory.warehouse', compact('inventories'));
    }

    public function shoulder(Request $request)
    {
        $query = Product::where('status', 1);

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query->orderBy('name')->get();
        $inventories = [];

        foreach ($products as $product) {
            $shoulder = ShoulderInventory::where('product_id', $product->id)->first();
            $stock = $shoulder ? $shoulder->stock : 0;

            $inventories[] = [
                'product' => $product,
                'stock' => $stock,
            ];
        }

        return view('inventory.shoulder', compact('inventories'));
    }

    /**
     * گزارش جامع موجودی‌ها (با ترتیب سفارشی)
     */
    public function allStocks(Request $request)
    {
        $showHidden = $request->input('show_hidden') == '1';

        $query = Product::where('status', 1);

        if (!$showHidden) {
            $query->where(function ($q) {
                $q->where('hidden_from_all_stocks', false)
                  ->orWhereNull('hidden_from_all_stocks');
            });
        }

        $products = $query
            ->orderByRaw('CASE WHEN all_stocks_sort_order > 0 THEN all_stocks_sort_order ELSE 999999 END ASC')
            ->orderBy('name')
            ->get();

        $stocks = collect();

        foreach ($products as $product) {
            $unpackaged = ShuttleFiring::where('kiln_type', 'kiln_3')
                ->where('firing_subtype', 'glaze')
                ->where('is_packaged', 0)
                ->where('product_id', $product->id)
                ->sum('output_quantity')
                -
                ShuttleFiring::where('kiln_type', 'kiln_4')
                ->where('is_packaged', 1)
                ->where('product_id', $product->id)
                ->sum('output_quantity');

            $unpackaged = max(0, $unpackaged);

            $raw = RawInventory::where('product_id', $product->id)->value('stock') ?? 0;

            // ✅ موجودی انبار = خودش + فرزندانش
            $warehouseStock = $this->calculateTotalWarehouseStock($product);

            $stocks->push((object) [
                'product' => $product,
                'opening' => OpeningInventory::where('product_id', $product->id)->sum('quantity'),
                'raw' => $raw,
                'wax' => $product->waxInventory->stock ?? 0,
                'glaze1300' => $product->glaze1300Inventory->stock ?? 0,
                'warehouse' => $warehouseStock,  // ✅ با احتساب فرزندان
                'shoulder' => $product->shoulderInventory->stock ?? 0,
                'waste_mum' => $product->wasteMumInventory->stock ?? 0,
                'unpackaged' => $unpackaged,
            ]);
        }

        return view('inventory.all-stocks', compact('stocks'));
    }

    // ============================================================
    //  مخفی/نمایش از گزارش جامع
    // ============================================================
    public function hideFromAllStocks(Product $product)
    {
        $product->hidden_from_all_stocks = true;
        $product->save();

        return back()->with('success', '✅ محصول از گزارش جامع مخفی شد.');
    }

    public function unhideFromAllStocks(Product $product)
    {
        $product->hidden_from_all_stocks = false;
        $product->save();

        return back()->with('success', '✅ محصول به گزارش جامع برگردانده شد.');
    }

    // ============================================================
    //  مخفی/نمایش از موجودی انبار
    // ============================================================
    public function hideFromWarehouse(Product $product)
    {
        $product->hidden_from_warehouse = true;
        $product->save();

        return back()->with('success', '✅ محصول از موجودی انبار مخفی شد.');
    }

    public function unhideFromWarehouse(Product $product)
    {
        $product->hidden_from_warehouse = false;
        $product->save();

        return back()->with('success', '✅ محصول به موجودی انبار برگردانده شد.');
    }

    // ============================================================
    //  به‌روزرسانی موجودی انبار از صفحه موجودی انبار
    // ============================================================
    public function updateWarehouseStock(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|string',
        ]);

        $rawInput = (string) $request->input('quantity');
        $rawInput = str_replace(
            ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹',
             '٠','١','٢','٣','٤','٥','٦','٧','٨','٩',
             '،'],
            ['0','1','2','3','4','5','6','7','8','9',
             '0','1','2','3','4','5','6','7','8','9',
             ''],
            $rawInput
        );

        $cleanQty = preg_replace('/[^0-9.]/', '', $rawInput);

        if ($cleanQty === '' || !is_numeric($cleanQty)) {
            return response()->json([
                'success' => false,
                'error'   => 'عدد معتبر وارد کنید.',
            ], 422);
        }

        $quantity = (float) $cleanQty;

        if ($quantity < 0) {
            return response()->json([
                'success' => false,
                'error'   => 'مقدار نمی‌تواند منفی باشد.',
            ], 422);
        }

        // ✅ اگه محصول فرزند داره، ذخیره روی خودش (موجودی نمایشی از جمع میاد)
        WarehouseInventory::updateOrCreate(
            ['product_id' => $product->id],
            ['stock' => $quantity]
        );

        // محاسبه مجدد موجودی کل (خودش + فرزندان)
        $totalStock = $this->calculateTotalWarehouseStock($product);

        $cartons = 0;
        $packs   = 0;
        $pallets = 0;

        if ($product->per_box && $product->per_box > 0) {
            $cartons = intval($totalStock / $product->per_box);
        }
        if ($product->per_pack && $product->per_pack > 0) {
            $packs = intval($totalStock / $product->per_pack);
        }
        if ($product->per_pallet && $product->per_pallet > 0) {
            $pallets = intval($totalStock / $product->per_pallet);
        }

        return response()->json([
            'success' => true,
            'stock'   => $totalStock,
            'cartons' => $cartons,
            'packs'   => $packs,
            'pallets' => $pallets,
            'message' => 'موجودی با موفقیت به‌روزرسانی شد.',
        ]);
    }

    // ============================================================
    //  ذخیره ترتیب سفارشی موجودی انبار
    // ============================================================
    public function reorderWarehouse(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:products,id',
        ]);

        $order = $request->input('order');

        DB::beginTransaction();
        try {
            foreach ($order as $index => $productId) {
                Product::where('id', $productId)
                    ->update(['warehouse_sort_order' => $index + 1]);
            }

            $allIds = Product::where('status', 1)->pluck('id')->toArray();
            $remaining = array_values(array_diff($allIds, $order));

            if (!empty($remaining)) {
                $startPos = count($order) + 1;

                $hiddenProducts = Product::whereIn('id', $remaining)
                    ->orderByRaw('CASE WHEN warehouse_sort_order > 0 THEN warehouse_sort_order ELSE 999999 END ASC')
                    ->orderBy('name')
                    ->pluck('id')
                    ->toArray();

                foreach ($hiddenProducts as $i => $productId) {
                    Product::where('id', $productId)
                        ->update(['warehouse_sort_order' => $startPos + $i]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب با موفقیت ذخیره شد.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('reorderWarehouse failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'خطا در ذخیره ترتیب: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    //  ذخیره ترتیب سفارشی گزارش جامع
    // ============================================================
    public function reorderAllStocks(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:products,id',
        ]);

        $order = $request->input('order');

        DB::beginTransaction();
        try {
            foreach ($order as $index => $productId) {
                Product::where('id', $productId)
                    ->update(['all_stocks_sort_order' => $index + 1]);
            }

            $allIds = Product::where('status', 1)->pluck('id')->toArray();
            $remaining = array_values(array_diff($allIds, $order));

            if (!empty($remaining)) {
                $startPos = count($order) + 1;

                $hiddenProducts = Product::whereIn('id', $remaining)
                    ->orderByRaw('CASE WHEN all_stocks_sort_order > 0 THEN all_stocks_sort_order ELSE 999999 END ASC')
                    ->orderBy('name')
                    ->pluck('id')
                    ->toArray();

                foreach ($hiddenProducts as $i => $productId) {
                    Product::where('id', $productId)
                        ->update(['all_stocks_sort_order' => $startPos + $i]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب با موفقیت ذخیره شد.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('reorderAllStocks failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'خطا در ذخیره ترتیب: ' . $e->getMessage(),
            ], 500);
        }
    }
}