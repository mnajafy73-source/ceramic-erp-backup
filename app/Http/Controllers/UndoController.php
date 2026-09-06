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
use App\Models\PackagingPurchase;
use App\Models\PackagingPurchaseItem;
use App\Models\Packaging;
use App\Models\MaterialMaking;
use App\Models\Formula;

class UndoController extends Controller
{
    /**
     * بازگرداندن عملیات حذف شده (Undo)
     */
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
                //  بازیابی گروهی
                // ============================================================
                $class = $record['class'];
                $data = $record['data'];

                // --- بازیابی گروهی مواد سازی ---
                if ($class === MaterialMaking::class) {
                    foreach ($data as $item) {
                        unset($item['id'], $item['created_at'], $item['updated_at']);
                        $newRecord = MaterialMaking::create($item);
                        $this->subtractMaterialsForFormula($newRecord->material, $newRecord->quantity, $newRecord->mill_weight);
                    }
                    session()->forget('undo_record');
                    DB::commit();
                    return back()->with('success', 'رکوردهای مواد سازی با موفقیت برگشت داده شدند.');
                }

                // --- بازیابی گروهی تولید (Production) ---
                if ($class === Production::class) {
                    foreach ($data as $item) {
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
                    DB::commit();
                    return back()->with('success', 'رکوردهای تولید با موفقیت برگشت داده شدند.');
                }

                // --- سایر بازیابی‌های گروهی در صورت نیاز ---
                // می‌توانید کلاس‌های دیگر را در اینجا اضافه کنید

            } else {
                // ============================================================
                //  بازیابی تکی
                // ============================================================
                $class = $record['class'];
                $data = $record['data'];
                $extra = $record['extra'] ?? [];

                $data['created_at'] = now();
                $data['updated_at'] = now();

                // تنظیم purchase_date برای خریدها (در صورت نیاز)
                if (!isset($data['purchase_date']) && isset($data['date'])) {
                    $data['purchase_date'] = $data['date'];
                }

                // --- بازیابی تکی مواد سازی ---
                if ($class === MaterialMaking::class) {
                    unset($data['id'], $data['created_at'], $data['updated_at']);
                    $newRecord = MaterialMaking::create($data);
                    // mill_weight در دیتابیس به گرم است، اما متد subtract انتظار کیلوگرم دارد
                    $this->subtractMaterialsForFormula($newRecord->material, $newRecord->quantity, $newRecord->mill_weight);
                    session()->forget('undo_record');
                    DB::commit();
                    return back()->with('success', 'رکورد مواد سازی با موفقیت برگشت داده شد.');
                }

                // --- بازیابی خرید کارتن و لایه ---
                if ($class === PackagingPurchase::class) {
                    $purchaseData = $data;
                    $itemsData = $extra['items'] ?? [];

                    if (empty($purchaseData['purchase_date'])) {
                        return back()->with('error', 'تاریخ خرید در داده‌های برگردانی وجود ندارد.');
                    }

                    $newPurchase = PackagingPurchase::create($purchaseData);

                    foreach ($itemsData as $itemData) {
                        unset($itemData['id'], $itemData['purchase_id'], $itemData['created_at'], $itemData['updated_at']);
                        $newPurchase->items()->create($itemData);

                        $packaging = Packaging::find($itemData['packaging_id']);
                        if ($packaging) {
                            $packaging->stock += $itemData['quantity'];
                            $packaging->save();
                        }
                    }

                    session()->forget('undo_record');
                    DB::commit();
                    return back()->with('success', 'خرید کارتن/لایه با موفقیت برگشت داده شد.');
                }

                // --- بازیابی خرید مواد اولیه ---
                if ($class === RawMaterialPurchase::class) {
                    $newPurchase = RawMaterialPurchase::create($data);

                    if (isset($extra['items']) && is_array($extra['items'])) {
                        foreach ($extra['items'] as $itemData) {
                            $quantityInGram = $itemData['quantity'];
                            $rawMaterialId = $itemData['raw_material_id'];

                            $newPurchase->items()->create($itemData);

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

                // --- بازیابی فروش (رسمی و غیررسمی) ---
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

                // --- سایر بازیابی‌های تکی در صورت نیاز ---

                session()->forget('undo_record');
            }

            DB::commit();
            return back()->with('success', 'عملیات با موفقیت برگشت داده شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در برگرداندن: ' . $e->getMessage());
        }
    }

    /**
     * متد کمکی برای کسر مجدد مواد اولیه هنگام بازگردانی موادسازی
     * 
     * @param string $formulaName نام فرمول
     * @param float $quantity تعداد بالمیل‌ها
     * @param float $millWeight وزن هر بالمیل به گرم (ذخیره‌شده در دیتابیس)
     */
    private function subtractMaterialsForFormula($formulaName, $quantity, $millWeight)
    {
        // تبدیل وزن بالمیل از گرم به کیلوگرم
        $millWeightKg = $millWeight / 1000;
        $totalKg = $quantity * $millWeightKg;

        $formula = Formula::where('name', $formulaName)->first();
        if (!$formula) {
            \Log::warning("فرمول '$formulaName' برای کسر مواد در Undo پیدا نشد.");
            return;
        }

        foreach ($formula->items as $item) {
            $consumedKg = ($totalKg * $item->percentage) / 100;
            $consumedGram = $consumedKg * 1000;

            $rawMaterial = RawMaterial::find($item->raw_material_id);
            if ($rawMaterial) {
                $rawMaterial->stock -= $consumedGram;
                $rawMaterial->save();
                \Log::info("کسر مواد در Undo: {$rawMaterial->name} - {$consumedGram} گرم (فرمول {$formulaName})");
            }
        }
    }

    /**
     * کاهش موجودی محصول (برای فروش)
     */
    private function decreaseStock($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $product->initial_stock = max(0, $product->initial_stock - $quantity);
        $product->save();
    }

    /**
     * لغو عملیات بازگردانی (Discard)
     */
    public function discard(Request $request)
    {
        session()->forget('undo_record');
        return back()->with('success', 'بازیابی لغو شد.');
    }
}