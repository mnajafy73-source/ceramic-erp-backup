<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Sale;
use App\Models\InformalSale;

class UndoController extends Controller
{
    public function restore(Request $request)
    {
        $record = session('undo_record');

        if (!$record) {
            return back()->with('error', 'امکان برگشت وجود ندارد.');
        }

        $class = $record['class'];
        $data = $record['data'];
        $products = $record['products'] ?? [];

        $data['created_at'] = now();
        $data['updated_at'] = now();

        if (isset($data['date']) && is_string($data['date']) && strpos($data['date'], 'T') !== false) {
            try {
                $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        DB::beginTransaction();

        try {
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

            DB::commit();
            session()->forget('undo_record');

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