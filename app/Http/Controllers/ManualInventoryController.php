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
use App\Models\RawInventory;
use App\Models\ShuttleFiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualInventoryController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();

        $openingInventories   = OpeningInventory::pluck('quantity', 'product_id')->toArray();
        $rawInventories       = RawInventory::pluck('stock', 'product_id')->toArray();
        $waxInventories       = WaxInventory::pluck('stock', 'product_id')->toArray();
        $glaze1300Inventories = Glaze1300Inventory::pluck('stock', 'product_id')->toArray();
        $warehouseInventories = WarehouseInventory::pluck('stock', 'product_id')->toArray();
        $shoulderInventories  = ShoulderInventory::pluck('stock', 'product_id')->toArray();
        $wasteMumInventories  = WasteMumInventory::pluck('stock', 'product_id')->toArray();

        $rawStocksCalculated = [];
        foreach ($products as $product) {
            $rawStocksCalculated[$product->id] = ShuttleFiring::getRawStock($product->id);
        }

        $rawMaterials = RawMaterial::orderBy('name')->get();
        $packagings   = Packaging::orderBy('type')->orderBy('name')->get();

        $inventoryData = [];

        $inventoryData['opening'] = [
            'label' => '📦 موجودی اول دوره',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($openingInventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['raw'] = [
            'label' => '📊 موجودی خام',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($rawInventories[$p->id] ?? 0),
                'hint'  => 'محاسبه سیستم: ' . number_format($rawStocksCalculated[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['wax'] = [
            'label' => '🔥 موجودی موم (۹۰۰ درجه)',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($waxInventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['glaze1300'] = [
            'label' => '🔥 موجودی ۱۳۰۰ درجه',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($glaze1300Inventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['warehouse'] = [
            'label' => '🏭 موجودی انبار',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($warehouseInventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['shoulder'] = [
            'label' => '🧴 موجودی شانه شده',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($shoulderInventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['waste_mum'] = [
            'label' => '🗑️ ضایعات موم',
            'unit'  => 'عدد',
            'items' => $products->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'value' => (float) ($wasteMumInventories[$p->id] ?? 0),
            ])->values()->toArray(),
        ];

        $inventoryData['raw_material'] = [
            'label' => '🧪 مواد اولیه',
            'unit'  => 'گرم',
            'items' => $rawMaterials->map(fn($m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'value' => (float) $m->stock,
            ])->values()->toArray(),
        ];

        $inventoryData['packaging'] = [
            'label' => '📦 کارتن و لایه',
            'unit'  => 'عدد',
            'items' => $packagings->map(fn($p) => [
                'id'    => $p->id,
                'name'  => ($p->type == 'carton' ? '[کارتن] ' : '[لایه] ') . $p->name,
                'value' => (float) $p->stock,
            ])->values()->toArray(),
        ];

        return view('settings.manual-inventory', compact('inventoryData'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'type'     => 'required|in:opening,raw,wax,glaze1300,warehouse,shoulder,waste_mum,raw_material,packaging',
            'item_id'  => 'required|integer|min:1',
            'quantity' => 'required|string',
        ]);

        $type = $request->input('type');
        $itemId = (int) $request->input('item_id');

        // تبدیل اعداد فارسی/عربی به لاتین
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
            return back()->withErrors(['quantity' => 'مقدار باید یک عدد معتبر باشد.']);
        }

        $quantity = (float) $cleanQty;

        if ($quantity < 0) {
            return back()->withErrors(['quantity' => 'مقدار نمی‌تواند منفی باشد.']);
        }

        $labels = [
            'opening'      => 'موجودی اول دوره',
            'raw'          => 'موجودی خام',
            'wax'          => 'موجودی موم',
            'glaze1300'    => 'موجودی ۱۳۰۰ درجه',
            'warehouse'    => 'موجودی انبار',
            'shoulder'     => 'موجودی شانه شده',
            'waste_mum'    => 'ضایعات موم',
            'raw_material' => 'مواد اولیه',
            'packaging'    => 'کارتن و لایه',
        ];

        DB::beginTransaction();
        try {
            switch ($type) {
                case 'opening':
                    OpeningInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['quantity' => $quantity]
                    );
                    break;

                case 'raw':
                    RawInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'wax':
                    WaxInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'glaze1300':
                    Glaze1300Inventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'warehouse':
                    WarehouseInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'shoulder':
                    ShoulderInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'waste_mum':
                    WasteMumInventory::updateOrCreate(
                        ['product_id' => $itemId],
                        ['stock' => $quantity]
                    );
                    break;

                case 'raw_material':
                    RawMaterial::where('id', $itemId)->update(['stock' => $quantity]);
                    break;

                case 'packaging':
                    Packaging::where('id', $itemId)->update(['stock' => $quantity]);
                    break;
            }

            DB::commit();

            return redirect()->route('settings.manual-inventory')
                ->with('success', "✅ {$labels[$type]} با مقدار {$quantity} به‌روزرسانی شد.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }
    }

    public function resetAll()
    {
        DB::beginTransaction();

        try {
            OpeningInventory::query()->update(['quantity' => 0]);
            RawInventory::query()->update(['stock' => 0]);
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