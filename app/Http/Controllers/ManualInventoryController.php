<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Packaging;
use App\Models\OpeningInventory;
use App\Models\WaxInventory;
use App\Models\Glaze1300Inventory;
use App\Models\WarehouseInventory;
use App\Models\ShoulderInventory;
use App\Models\WasteMumInventory;
use App\Models\ShuttleFiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualInventoryController extends Controller
{
    /**
     * حذف ویرگول از اعداد ورودی
     */
    protected function cleanNumber($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return str_replace(',', '', $value);
    }

    /**
     * نمایش فرم به‌روزرسانی دستی همه موجودی‌ها
     */
    public function index()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();

        $openingInventories = OpeningInventory::with('product')->get()->keyBy('product_id');
        $rawStocks = [];
        foreach ($products as $product) {
            $rawStocks[$product->id] = ShuttleFiring::getRawStock($product->id);
        }
        $waxInventories = WaxInventory::with('product')->get()->keyBy('product_id');
        $glaze1300Inventories = Glaze1300Inventory::with('product')->get()->keyBy('product_id');
        $warehouseInventories = WarehouseInventory::with('product')->get()->keyBy('product_id');
        $shoulderInventories = ShoulderInventory::with('product')->get()->keyBy('product_id');
        $wasteMumInventories = WasteMumInventory::with('product')->get()->keyBy('product_id');

        $rawMaterials = RawMaterial::orderBy('name')->get();
        $packagings = Packaging::orderBy('type')->orderBy('name')->get();

        return view('settings.manual-inventory', compact(
            'products',
            'openingInventories',
            'rawStocks',
            'waxInventories',
            'glaze1300Inventories',
            'warehouseInventories',
            'shoulderInventories',
            'wasteMumInventories',
            'rawMaterials',
            'packagings'
        ));
    }

    /**
     * ذخیره‌سازی مقادیر ویرایش‌شده
     */
    public function update(Request $request)
    {
        // پاک کردن ویرگول‌ها از همه ورودی‌ها
        $cleanedData = $request->all();

        // پاک کردن ویرگول از آرایه‌ها
        $fields = ['opening', 'wax', 'glaze1300', 'warehouse', 'shoulder', 'waste_mum', 'raw_material', 'packaging'];
        foreach ($fields as $field) {
            if (isset($cleanedData[$field]) && is_array($cleanedData[$field])) {
                foreach ($cleanedData[$field] as $key => $value) {
                    $cleanedData[$field][$key] = $this->cleanNumber($value);
                }
            }
        }

        $request->merge($cleanedData);

        $request->validate([
            'opening' => 'nullable|array',
            'opening.*' => 'nullable|numeric|min:0',
            'wax' => 'nullable|array',
            'wax.*' => 'nullable|numeric|min:0',
            'glaze1300' => 'nullable|array',
            'glaze1300.*' => 'nullable|numeric|min:0',
            'warehouse' => 'nullable|array',
            'warehouse.*' => 'nullable|numeric|min:0',
            'shoulder' => 'nullable|array',
            'shoulder.*' => 'nullable|numeric|min:0',
            'waste_mum' => 'nullable|array',
            'waste_mum.*' => 'nullable|numeric|min:0',
            'raw_material' => 'nullable|array',
            'raw_material.*' => 'nullable|numeric|min:0',
            'packaging' => 'nullable|array',
            'packaging.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            if ($request->has('opening')) {
                foreach ($request->opening as $productId => $quantity) {
                    if ($quantity !== null && $quantity !== '') {
                        OpeningInventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['quantity' => $quantity]
                        );
                    }
                }
            }

            if ($request->has('wax')) {
                foreach ($request->wax as $productId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        WaxInventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['stock' => $stock]
                        );
                    }
                }
            }

            if ($request->has('glaze1300')) {
                foreach ($request->glaze1300 as $productId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        Glaze1300Inventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['stock' => $stock]
                        );
                    }
                }
            }

            if ($request->has('warehouse')) {
                foreach ($request->warehouse as $productId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        WarehouseInventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['stock' => $stock]
                        );
                    }
                }
            }

            if ($request->has('shoulder')) {
                foreach ($request->shoulder as $productId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        ShoulderInventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['stock' => $stock]
                        );
                    }
                }
            }

            if ($request->has('waste_mum')) {
                foreach ($request->waste_mum as $productId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        WasteMumInventory::updateOrCreate(
                            ['product_id' => $productId],
                            ['stock' => $stock]
                        );
                    }
                }
            }

            if ($request->has('raw_material')) {
                foreach ($request->raw_material as $materialId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        RawMaterial::where('id', $materialId)->update(['stock' => $stock]);
                    }
                }
            }

            if ($request->has('packaging')) {
                foreach ($request->packaging as $packagingId => $stock) {
                    if ($stock !== null && $stock !== '') {
                        Packaging::where('id', $packagingId)->update(['stock' => $stock]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('settings.manual-inventory')
                ->with('success', '✅ همه موجودی‌ها با موفقیت به‌روزرسانی شدند.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }
    }

    /**
     * صفر کردن همه موجودی‌ها (با احتیاط)
     */
    public function resetAll()
    {
        DB::beginTransaction();

        try {
            OpeningInventory::query()->update(['quantity' => 0]);
            WaxInventory::query()->update(['stock' => 0]);
            Glaze1300Inventory::query()->update(['stock' => 0]);
            WarehouseInventory::query()->update(['stock' => 0]);
            ShoulderInventory::query()->update(['stock' => 0]);
            WasteMumInventory::query()->update(['stock' => 0]);
            RawMaterial::query()->update(['stock' => 0]);
            Packaging::query()->update(['stock' => 0]);

            DB::commit();

            return redirect()->route('settings.manual-inventory')
                ->with('success', '✅ همه موجودی‌ها با موفقیت صفر شدند.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در صفر کردن موجودی‌ها: ' . $e->getMessage()]);
        }
    }
}