<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Sale;
use App\Models\InformalSale;
use App\Models\Production;
use App\Models\RawMaterialPurchase;
use App\Models\RawMaterial;

class UndoController extends Controller
{
    public function restore(Request $request)
    {
        $record = session('undo_record');

        if (!$record) {
            return back()->with('error', 'امکان برگشت وجود ندارد.');
        }

        $isGroup = isset($record['multiple']) && $record['multiple'] === true;

        DB::beginTransaction();

        try {
            if ($isGroup) {
                // ============================================================
                //  بازیابی گروهی (برای Production)
                // ============================================================
                $class = $record['class'];
                $items = $record['data'];

                foreach ($items as $item) {
                    $productionData = $item['production'];
                    $stopsData = $item['stops'] ?? [];

                    unset($productionData['id'], $productionData['created_at'], $productionData['updated_at']);
                    $productionData['created_at'] = now();
                    $productionData['updated_at'] = now();

                    if (isset($productionData['date']) && is_string($productionData['date']) && strpos($productionData['date'], 'T') !== false) {
                        try {
                            $productionData['date'] = Carbon::parse($productionData['date'])->format('Y-m-d');
                        } catch (\Exception $e) {}
                    }

                    $newId = DB::table((new $class())->getTable())->insertGetId($productionData);

                    foreach ($stopsData as $stopData) {
                        unset($stopData['id'], $stopData['production_id'], $stopData['created_at'], $stopData['updated_at']);
                        $stopData['production_id'] = $newId;
                        $stopData['created_at'] = now();
                        $stopData['updated_at'] = now();
                        DB::table('production_stops')->insert($stopData);
                    }
                }

                session()->forget('undo_record');

            } else {
                // ============================================================
                //  بازیابی تکی
                // ============================================================
                $class = $record['class'];
                $data = $record['data'];
                $extra = $record['extra'] ?? [];

                $data['created_at'] = now();
                $data['updated_at'] = now();

                if (isset($data['date']) && is_string($data['date']) && strpos($data['date'], 'T') !== false) {
                    try {
                        $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
                    } catch (\Exception $e) {}
                }

                // ============================================================
                //  بازیابی خرید مواد اولیه (RawMaterialPurchase)
                // ============================================================
                if ($class === RawMaterialPurchase::class) {
                    // ایجاد خرید جدید
                    $newPurchase = RawMaterialPurchase::create($data);

                    // بازیابی آیتم‌ها و افزایش موجودی
                    if (isset($extra['items']) && is_array($extra['items'])) {
                        foreach ($extra['items'] as $itemData) {
                            $quantityInGram = $itemData['quantity'];
                            $rawMaterialId = $itemData['raw_material_id'];

                            // ایجاد آیتم
                            $newPurchase->items()->create($itemData);

                            // افزایش موجودی به اندازه مقدار خرید (چون حذف، موجودی را کم کرده بود)
                            $rawMaterial = RawMaterial::find($rawMaterialId);
                            if ($rawMaterial) {
                                $rawMaterial->stock += $quantityInGram;
                                $rawMaterial->save();
                            }
                        }
                    }

                    session()->forget('undo_record');
                    DB::commit();
                    return back()->with('success', 'خرید مواد با موفقیت برگشت داده شد.');
                }

                // ============================================================
                //  بازیابی فروش (Sale / InformalSale)
                // ============================================================
                $products = $extra['products'] ?? [];

                $newId = DB::table((new $class())->getTable())->insertGetId($data);

                if (!empty($products) && is_array($products)) {
                    $isSale = ($class === Sale::class);
                    $isInformalSale = ($class === InformalSale::class);

                    foreach ($products as $product) {
                        unset($product['id']);
                        unset($product['sale_id']);
                        unset($product['informal_sale_id']);

                        if ($isSale) {
                            $product['sale_id'] = $newId;
                            DB::table('sale_products')->insert($product);
                            $this->decreaseStock($product['product_id'], $product['quantity']);
                        } elseif ($isInformalSale) {
                            $product['informal_sale_id'] = $newId;
                            DB::table('informal_sale_products')->insert($product);
                            $this->decreaseStock($product['product_id'], $product['quantity']);
                        }
                    }
                }

                session()->forget('undo_record');
            }

            DB::commit();
            return back()->with('success', 'عملیات با موفقیت برگشت داده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در برگرداندن: ' . $e->getMessage());
        }
    }

    private function decreaseStock($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock = max(0, $product->initial_stock - $quantity);
        $product->save();
    }

    public function discard(Request $request)
    {
        session()->forget('undo_record');
        return back()->with('success', 'بازیابی لغو شد.');
    }
}