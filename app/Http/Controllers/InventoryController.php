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
use App\Models\TonneliFiringItem;
use App\Models\InventoryChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ API آخرین تغییرات
    // ═══════════════════════════════════════════════════════════
    public function getChangeLogs(Request $request)
    {
        $request->validate([
            'type'  => 'required|string',
            'id'    => 'required|integer',
            'field' => 'nullable|string',
        ]);

        $typeMap = [
            'raw-material'        => RawMaterial::class,
            'packaging'           => Packaging::class,
            'warehouse'           => WarehouseInventory::class,
            'raw-inventory'       => RawInventory::class,
            'wax-inventory'       => WaxInventory::class,
            'shoulder-inventory'  => ShoulderInventory::class,
            'waste-mum-inventory' => WasteMumInventory::class,
            'glaze1300-inventory' => Glaze1300Inventory::class,
            'product'             => Product::class,
        ];

        $className = $typeMap[$request->type] ?? null;
        if (!$className) {
            return response()->json(['success' => false, 'error' => 'نوع نامعتبر']);
        }

        $query = InventoryChangeLog::where('loggable_type', $className)
            ->where('loggable_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->limit(5);

        if ($request->filled('field')) {
            $query->where('field', $request->field);
        }

        $logs = $query->get()->map(function ($log) {
            return [
                'jalali'    => Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i'),
                'old_value' => (float) $log->old_value,
                'new_value' => (float) $log->new_value,
                'mode'      => $log->mode,
                'user'      => $log->user?->name ?? '—',
            ];
        });

        return response()->json([
            'success' => true,
            'logs'    => $logs,
        ]);
    }

    public function rawMaterialsStock(Request $request)
    {
        $query = RawMaterial::query();

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $materials = $query
            ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 999999 END ASC')
            ->orderBy('name')
            ->get();

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
            $rawInv = RawInventory::where('product_id', $product->id)->first();
            if ($rawInv !== null && $rawInv->stock > 0) {
                $raw = $rawInv->stock;
            } else {
                $raw = ShuttleFiring::getRawStock($product->id);
            }

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

    public function packagingStock(Request $request)
    {
        $query = Packaging::query();

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $packagings = $query
            ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 999999 END ASC')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('inventory.packaging-stock', compact('packagings'));
    }

    private function calculateTotalWarehouseStock(Product $product)
    {
        $warehouse = WarehouseInventory::where('product_id', $product->id)->first();
        $total = $warehouse ? (float) $warehouse->stock : 0;

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

    private function calculateUnpackagedStock(Product $product)
    {
        if ($product->unpackaged_manual_stock !== null) {
            return (float) $product->unpackaged_manual_stock;
        }

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

        return max(0, (float) $unpackaged);
    }

    private function calculatePackagingConsumed(Packaging $packaging)
    {
        $total = 0;

        foreach (TonneliFiringItem::with('product')->where('is_packaged', 1)->where('output_quantity', '>', 0)->get() as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;

            if ($product->carton_packaging_id == $packaging->id && $product->per_box > 0) {
                $total += ceil($qty / $product->per_box);
            }
            if ($product->layer_packaging_id == $packaging->id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $total += ceil($qty / $product->per_box) * $product->layers_per_box;
            }
        }

        foreach (ShuttleFiring::with('product')->where('is_packaged', 1)->where('output_quantity', '>', 0)->get() as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;

            if ($product->carton_packaging_id == $packaging->id && $product->per_box > 0) {
                $total += ceil($qty / $product->per_box);
            }
            if ($product->layer_packaging_id == $packaging->id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $total += ceil($qty / $product->per_box) * $product->layers_per_box;
            }
        }

        return $total;
    }

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

        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }

        $products = $query
            ->orderByRaw('CASE WHEN all_stocks_sort_order > 0 THEN all_stocks_sort_order ELSE 999999 END ASC')
            ->orderBy('name')
            ->get();

        $stocks = collect();

        foreach ($products as $product) {
            $unpackaged = $this->calculateUnpackagedStock($product);
            $isManualUnpackaged = $product->unpackaged_manual_stock !== null;

            $rawInv = RawInventory::where('product_id', $product->id)->first();
            if ($rawInv !== null && $rawInv->stock > 0) {
                $raw = $rawInv->stock;
            } else {
                $raw = ShuttleFiring::getRawStock($product->id);
            }

            $warehouseStock = $this->calculateTotalWarehouseStock($product);

            $stocks->push((object) [
                'product' => $product,
                'opening' => OpeningInventory::where('product_id', $product->id)->sum('quantity'),
                'raw' => $raw,
                'wax' => $product->waxInventory->stock ?? 0,
                'glaze1300' => $product->glaze1300Inventory->stock ?? 0,
                'warehouse' => $warehouseStock,
                'shoulder' => $product->shoulderInventory->stock ?? 0,
                'waste_mum' => $product->wasteMumInventory->stock ?? 0,
                'unpackaged' => $unpackaged,
                'is_manual_unpackaged' => $isManualUnpackaged,
            ]);
        }

        return view('inventory.all-stocks', compact('stocks'));
    }

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

    public function updateWarehouseStock(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|string',
            'mode'     => 'nullable|in:set,adjust',
        ]);

        $mode = $request->input('mode', 'set');

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

        $cleanQty = preg_replace('/[^0-9.\-]/', '', $rawInput);

        if ($cleanQty === '' || !is_numeric($cleanQty)) {
            return response()->json([
                'success' => false,
                'error'   => 'عدد معتبر وارد کنید.',
            ], 422);
        }

        $quantity = (float) $cleanQty;

        if ($mode === 'set' && $quantity < 0) {
            return response()->json([
                'success' => false,
                'error'   => 'مقدار نمی‌تواند منفی باشد.',
            ], 422);
        }

        $inv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
        $oldStock = (float) $inv->stock;

        if ($mode === 'adjust') {
            $inv->stock = max(0, $oldStock + $quantity);
        } else {
            $inv->stock = max(0, $quantity);
        }
        $inv->save();

        // ✅ لاگ تغییر — با product_id
        if ($oldStock != (float) $inv->stock) {
            InventoryChangeLog::log($inv, 'warehouse', $oldStock, (float) $inv->stock, $mode, $product->id);
        }

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

        $modeLabel = ($mode === 'adjust') ? ' (کسر/اضافه)' : '';

        return response()->json([
            'success' => true,
            'stock'   => $totalStock,
            'cartons' => $cartons,
            'packs'   => $packs,
            'pallets' => $pallets,
            'message' => 'موجودی با موفقیت به‌روزرسانی شد' . $modeLabel . '.',
        ]);
    }

    public function updateRawMaterialStock(Request $request, RawMaterial $material)
    {
        $request->validate([
            'quantity' => 'required|string',
            'unit'     => 'nullable|in:gram,ton',
            'mode'     => 'nullable|in:set,adjust',
        ]);

        $unit = $request->input('unit', 'gram');
        $mode = $request->input('mode', 'set');

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

        $cleanQty = preg_replace('/[^0-9.\-]/', '', $rawInput);

        if ($cleanQty === '' || !is_numeric($cleanQty)) {
            return response()->json([
                'success' => false,
                'error'   => 'عدد معتبر وارد کنید.',
            ], 422);
        }

        $quantity = (float) $cleanQty;

        if ($mode === 'set' && $quantity < 0) {
            return response()->json([
                'success' => false,
                'error'   => 'مقدار نمی‌تواند منفی باشد.',
            ], 422);
        }

        $quantityInGram = ($unit === 'ton') ? $quantity * 1000000 : $quantity;
        $oldStock = (float) $material->stock;

        if ($mode === 'adjust') {
            $newStock = max(0, $oldStock + $quantityInGram);
        } else {
            $newStock = max(0, $quantityInGram);
        }

        $material->stock = $newStock;
        $material->save();

        // ✅ لاگ تغییر
        if ($oldStock != $newStock) {
            InventoryChangeLog::log($material, 'stock', $oldStock, $newStock, $mode);
        }

        $modeLabel = ($mode === 'adjust') ? ' (کسر/اضافه)' : '';

        return response()->json([
            'success'      => true,
            'stock'        => $newStock,
            'stock_in_ton' => $newStock / 1000000,
            'message'      => 'موجودی با موفقیت به‌روزرسانی شد' . $modeLabel . '.',
        ]);
    }

    public function updatePackagingStock(Request $request, Packaging $packaging)
    {
        $request->validate([
            'quantity' => 'required|string',
            'mode'     => 'nullable|in:set,adjust',
        ]);

        $mode = $request->input('mode', 'set');

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

        $cleanQty = preg_replace('/[^0-9.\-]/', '', $rawInput);

        if ($cleanQty === '' || !is_numeric($cleanQty)) {
            return response()->json([
                'success' => false,
                'error'   => 'عدد معتبر وارد کنید.',
            ], 422);
        }

        $quantity = (float) $cleanQty;

        if ($mode === 'set' && $quantity < 0) {
            return response()->json([
                'success' => false,
                'error'   => 'مقدار نمی‌تواند منفی باشد.',
            ], 422);
        }

        $oldStock = (float) $packaging->stock;

        if ($mode === 'adjust') {
            $newStock = max(0, $oldStock + $quantity);
        } else {
            $newStock = max(0, $quantity);
        }

        $packaging->stock = (int) $newStock;

        // ✅ مبنای مصرف رو ریست کن به مصرف فعلی
        $packaging->baseline_consumed = $this->calculatePackagingConsumed($packaging);

        $packaging->save();

        // ✅ لاگ تغییر
        if ($oldStock != $newStock) {
            InventoryChangeLog::log($packaging, 'stock', $oldStock, $newStock, $mode);
        }

        $modeLabel = ($mode === 'adjust') ? ' (کسر/اضافه)' : '';

        return response()->json([
            'success'            => true,
            'stock'              => $packaging->stock,
            'baseline_consumed'  => $packaging->baseline_consumed,
            'message'            => 'موجودی ذخیره شد' . $modeLabel . '. از این لحظه، فقط مصرف‌های جدید از این عدد کم می‌شه.',
        ]);
    }

    public function updateAllStocksField(Request $request, Product $product)
    {
        $request->validate([
            'field'    => 'required|in:raw,wax,shoulder,waste_mum,glaze1300,warehouse,unpackaged',
            'quantity' => 'required|string',
            'mode'     => 'nullable|in:set,adjust',
        ]);

        $field = $request->input('field');
        $mode  = $request->input('mode', 'set');

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

        $cleanQty = preg_replace('/[^0-9.\-]/', '', $rawInput);

        if ($field === 'unpackaged' && ($cleanQty === '' || $rawInput === 'auto')) {
            $product->unpackaged_manual_stock = null;
            $product->save();

            return response()->json([
                'success' => true,
                'stock'   => 0,
                'is_auto' => true,
                'message' => 'مقدار به حالت محاسبه خودکار برگشت.',
            ]);
        }

        if ($cleanQty === '' || !is_numeric($cleanQty)) {
            return response()->json([
                'success' => false,
                'error'   => 'عدد معتبر وارد کنید.',
            ], 422);
        }

        $quantity = (float) $cleanQty;

        if ($mode === 'set' && $quantity < 0) {
            return response()->json([
                'success' => false,
                'error'   => 'مقدار نمی‌تواند منفی باشد.',
            ], 422);
        }

        if ($field === 'warehouse') {
            $hasChildren = $product->children()->where('status', 1)->exists();
            if ($hasChildren) {
                return response()->json([
                    'success' => false,
                    'error'   => 'این محصول فرزند دارد. برای ویرایش انبار، از صفحه «موجودی انبار» استفاده کنید.',
                ], 422);
            }
        }

        try {
            $newStock = 0;
            $oldStock = 0;
            $loggable = null;

            switch ($field) {
                case 'raw':
                    $inv = RawInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'wax':
                    $inv = WaxInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'shoulder':
                    $inv = ShoulderInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'waste_mum':
                    $inv = WasteMumInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'glaze1300':
                    $inv = Glaze1300Inventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'warehouse':
                    $inv = WarehouseInventory::firstOrCreate(['product_id' => $product->id]);
                    $oldStock = (float) $inv->stock;
                    if ($mode === 'adjust') {
                        $inv->stock = max(0, $oldStock + $quantity);
                    } else {
                        $inv->stock = $quantity;
                    }
                    $inv->save();
                    $newStock = (float) $inv->stock;
                    $loggable = $inv;
                    break;

                case 'unpackaged':
                    $oldStock = (float) $this->calculateUnpackagedStock($product);
                    if ($mode === 'adjust') {
                        $product->unpackaged_manual_stock = max(0, $oldStock + $quantity);
                    } else {
                        $product->unpackaged_manual_stock = $quantity;
                    }
                    $product->save();
                    $newStock = (float) $product->unpackaged_manual_stock;
                    $loggable = $product;
                    break;
            }

            // ✅ لاگ تغییر — با product_id
            if ($loggable && $oldStock != $newStock) {
                InventoryChangeLog::log($loggable, $field, $oldStock, $newStock, $mode, $product->id);
            }

            $modeLabel = ($mode === 'adjust') ? ' (کسر/اضافه)' : '';

            return response()->json([
                'success' => true,
                'stock'   => $newStock,
                'is_auto' => false,
                'message' => 'موجودی با موفقیت به‌روزرسانی شد' . $modeLabel . '.',
            ]);

        } catch (\Exception $e) {
            \Log::error('updateAllStocksField failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'خطا در ذخیره‌سازی: ' . $e->getMessage(),
            ], 500);
        }
    }

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

    public function reorderRawMaterials(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:raw_materials,id',
        ]);

        $order = $request->input('order');

        DB::beginTransaction();
        try {
            foreach ($order as $index => $materialId) {
                RawMaterial::where('id', $materialId)
                    ->update(['sort_order' => $index + 1]);
            }

            $allIds = RawMaterial::pluck('id')->toArray();
            $remaining = array_values(array_diff($allIds, $order));

            if (!empty($remaining)) {
                $startPos = count($order) + 1;

                $hiddenMaterials = RawMaterial::whereIn('id', $remaining)
                    ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 999999 END ASC')
                    ->orderBy('name')
                    ->pluck('id')
                    ->toArray();

                foreach ($hiddenMaterials as $i => $materialId) {
                    RawMaterial::where('id', $materialId)
                        ->update(['sort_order' => $startPos + $i]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب با موفقیت ذخیره شد.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('reorderRawMaterials failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'خطا در ذخیره ترتیب: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reorderPackagings(Request $request)
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:packagings,id',
        ]);

        $order = $request->input('order');

        DB::beginTransaction();
        try {
            foreach ($order as $index => $packagingId) {
                Packaging::where('id', $packagingId)
                    ->update(['sort_order' => $index + 1]);
            }

            $allIds = Packaging::pluck('id')->toArray();
            $remaining = array_values(array_diff($allIds, $order));

            if (!empty($remaining)) {
                $startPos = count($order) + 1;

                $hiddenItems = Packaging::whereIn('id', $remaining)
                    ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 999999 END ASC')
                    ->orderBy('type')
                    ->orderBy('name')
                    ->pluck('id')
                    ->toArray();

                foreach ($hiddenItems as $i => $packagingId) {
                    Packaging::where('id', $packagingId)
                        ->update(['sort_order' => $startPos + $i]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب با موفقیت ذخیره شد.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('reorderPackagings failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'خطا در ذخیره ترتیب: ' . $e->getMessage(),
            ], 500);
        }
    }
}