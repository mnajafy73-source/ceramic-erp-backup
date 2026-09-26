<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionStop;
use App\Models\TonneliFiring;
use App\Models\TonneliFiringItem;
use App\Models\ShuttleFiring;
use App\Models\Operator;
use App\Models\Press;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\Packaging;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\InformalSale;
use App\Models\InformalSaleProduct;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\ShoulderRecord;
use App\Models\WasteMumRecord;
use App\Models\MaterialMaking;
use App\Models\Formula;
use App\Models\RawMaterial;
use App\Models\RawInventory;
use App\Models\WaxInventory;
use App\Models\Glaze1300Inventory;
use App\Models\WarehouseInventory;
use App\Models\ShoulderInventory;
use App\Models\WasteMumInventory;
use App\Models\InventoryChangeLog;
use App\Helpers\ImportFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index()
    {
        return view('import.index');
    }

    public function importFromPath(Request $request)
    {
        $filePath = env('EXCEL_FILE_PATH');

        if (empty($filePath) || !file_exists($filePath)) {
            $msg = 'مسیر فایل اکسل در فایل .env تنظیم نشده یا فایل وجود ندارد.';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        ImportFlag::$isImporting = true;
        $errors = [];
        $anySuccess = false;

        try {
            set_time_limit(0);

            DB::statement('PRAGMA foreign_keys = OFF');

            try {
                DB::statement('
                    DELETE FROM production_stops
                    WHERE production_id IN (
                        SELECT id FROM productions WHERE is_imported = 1
                    )
                ');
                DB::statement('DELETE FROM productions WHERE is_imported = 1');

                DB::statement('
                    DELETE FROM tonneli_firing_items
                    WHERE tonneli_firing_id IN (
                        SELECT id FROM tonneli_firings WHERE is_imported = 1
                    )
                ');
                DB::statement('DELETE FROM tonneli_firings WHERE is_imported = 1');

                DB::statement('DELETE FROM shuttle_firings WHERE is_imported = 1');

                DB::statement('DELETE FROM material_makings WHERE is_imported = 1');

                DB::statement('
                    DELETE FROM sale_products
                    WHERE sale_id IN (
                        SELECT id FROM sales WHERE is_imported = 1
                    )
                ');
                DB::statement('DELETE FROM sales WHERE is_imported = 1');

                DB::statement('DELETE FROM shoulder_records');
                DB::statement('DELETE FROM waste_mum_records');
                DB::statement('DELETE FROM informal_sale_products');
                DB::statement('DELETE FROM informal_sales');
            } finally {
                DB::statement('PRAGMA foreign_keys = ON');
            }

            $beforeSnapshot = $this->takeInventorySnapshot();

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            $steps = [
                'importProductionsFromSpreadsheet'      => 'تولید',
                'importTonneliFromSpreadsheet'          => 'کوره تونلی',
                'importShuttleFromSpreadsheet'          => 'کوره شاتل',
                'importInformalSalesFromSpreadsheet'    => 'فروش غیررسمی',
                'importFormalSalesFromSpreadsheet'      => 'فروش رسمی',
                'importShoulderFromSpreadsheet'         => 'شانه زنی',
                'importMaterialMakingFromSpreadsheet'   => 'مواد سازی',
                'importCustomerPaymentsFromSpreadsheet' => 'حسابداری (پرداخت‌ها)',
            ];

            foreach ($steps as $method => $label) {
                try {
                    $this->$method($spreadsheet);
                    $anySuccess = true;
                } catch (\Exception $e) {
                    $errors[] = "خطا در برگه {$label}: " . $e->getMessage();
                    Log::error("{$method} failed: " . $e->getMessage());
                }
            }

            ImportFlag::$isImporting = false;
            $afterSnapshot = $this->takeInventorySnapshot();
            $this->applyInventoryDeltas($beforeSnapshot, $afterSnapshot);

        } catch (\Exception $e) {
            ImportFlag::$isImporting = false;
            $msg = 'خطا در خواندن فایل: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        ImportFlag::$isImporting = false;

        if ($anySuccess) {
            $message = 'واردات خودکار با موفقیت انجام شد و موجودی‌ها به‌روز شدند.';
            if (!empty($errors)) {
                $message .= ' ⚠️ اما برخی خطاها رخ داد: ' . implode(' | ', $errors);
            }
            $status = 'success';
        } else {
            $message = 'هیچ برگه‌ای با موفقیت وارد نشد. خطاها: ' . implode(' | ', $errors);
            $status = 'error';
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => $status, 'message' => $message]);
        }
        return redirect()->back()->with($status === 'success' ? 'success' : 'error', $message);
    }

    private function importCustomerPaymentsFromSpreadsheet($spreadsheet)
    {
        $sheetNames = ['حسابداری', 'پرداخت‌ها', 'پرداختی‌ها', 'پرداخت'];
        $sheet = null;
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) break;
        }
        if (!$sheet) return;

        $rows = $sheet->toArray();
        array_shift($rows);

        DB::statement('DELETE FROM customer_payments WHERE is_imported = 1 OR is_imported IS NULL');

        $existingKeys = [];
        foreach (CustomerPayment::all() as $p) {
            $key = $p->year . '|' . $p->month . '|' . $p->day . '|' . $p->customer_name . '|' . $p->amount;
            $existingKeys[$key] = true;
        }

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    if (empty(array_filter($row))) continue;
                    $row = array_pad($row, 7, '');

                    $year   = (int) trim($row[0] ?? 0);
                    $month  = (int) trim($row[1] ?? 0);
                    $day    = (int) trim($row[2] ?? 0);
                    $name   = trim($row[3] ?? '');
                    $amount = (float) str_replace(',', '', trim($row[4] ?? 0));
                    $method = trim($row[5] ?? '');
                    $desc   = trim($row[6] ?? '');

                    if ($year < 1400 || $month < 1 || $month > 12 || $day < 1 || $day > 31) continue;
                    if (empty($name) || $amount <= 0) continue;

                    $key = $year . '|' . $month . '|' . $day . '|' . $name . '|' . $amount;
                    if (isset($existingKeys[$key])) continue;
                    $existingKeys[$key] = true;

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        continue;
                    }

                    $customer = Customer::firstOrCreate(
                        ['name' => $name],
                        ['status' => 1]
                    );

                    CustomerPayment::create([
                        'customer_id'    => $customer->id,
                        'customer_name'  => $name,
                        'amount'         => $amount,
                        'payment_date'   => $gregorianDate,
                        'year'           => $year,
                        'month'          => $month,
                        'day'            => $day,
                        'payment_method' => $method ?: null,
                        'description'    => $desc ?: null,
                        'is_imported'    => true,
                    ]);
                } catch (\Exception $e) {
                    Log::warning("خطا در ردیف پرداخت: " . $e->getMessage());
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function takeInventorySnapshot()
    {
        $snapshot = [
            'tonneli_packaged'       => [],
            'shuttle_k1_packaged'    => [],
            'shuttle_k2_packaged'    => [],
            'shuttle_k4_packaged'    => [],
            'shuttle_packaging_only' => [],
            'formal_sales'   => [],
            'informal_sales' => [],
            'raw_production'     => [],
            'raw_tonneli_input'  => [],
            'raw_shuttle_output' => [],
            'glaze1300_k2_output' => [],
            'glaze1300_packaged'  => [],
            'mum_k3_output' => [],
            'shoulder_records' => [],
            'waste_records'    => [],
            'raw_material_used' => [],
            'packaging_used' => [],
        ];

        $snapshot['formal_sales'] = SaleProduct::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();
        $snapshot['informal_sales'] = InformalSaleProduct::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['tonneli_packaged'] = TonneliFiringItem::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['shuttle_k1_packaged'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_1')->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['shuttle_k2_packaged'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_2')->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['glaze1300_k2_output'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_2')
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['shuttle_k4_packaged'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_4')->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['shuttle_packaging_only'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'packaging')
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['mum_k3_output'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_3')->where('firing_subtype', 'mum')
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['raw_production'] = Production::select('product_id', DB::raw('SUM(quantity) as total'))
            ->whereNotNull('press_id')
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['raw_tonneli_input'] = TonneliFiringItem::select('product_id', DB::raw('SUM(input_quantity) as total'))
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $snapshot['raw_shuttle_output'] = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $rawConsumed = [];
        foreach (MaterialMaking::all() as $record) {
            $formula = Formula::where('name', $record->material)->first();
            if (!$formula) continue;
            $totalGram = $record->quantity * $record->mill_weight;
            foreach ($formula->items as $item) {
                if (!isset($rawConsumed[$item->raw_material_id])) {
                    $rawConsumed[$item->raw_material_id] = 0;
                }
                $rawConsumed[$item->raw_material_id] += ($totalGram * $item->percentage) / 100;
            }
        }
        $snapshot['raw_material_used'] = $rawConsumed;

        $packagingConsumed = [];
        foreach (TonneliFiringItem::with('product')->where('is_packaged', 1)->where('output_quantity', '>', 0)->get() as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;
            if ($product->carton_packaging_id && $product->per_box > 0) {
                $count = ceil($qty / $product->per_box);
                $packagingConsumed[$product->carton_packaging_id] = ($packagingConsumed[$product->carton_packaging_id] ?? 0) + $count;
            }
            if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $count = ceil($qty / $product->per_box) * $product->layers_per_box;
                $packagingConsumed[$product->layer_packaging_id] = ($packagingConsumed[$product->layer_packaging_id] ?? 0) + $count;
            }
        }
        foreach (ShuttleFiring::with('product')->where('is_packaged', 1)->where('output_quantity', '>', 0)->get() as $item) {
            $product = $item->product;
            if (!$product) continue;
            $qty = $item->output_quantity;
            if ($product->carton_packaging_id && $product->per_box > 0) {
                $count = ceil($qty / $product->per_box);
                $packagingConsumed[$product->carton_packaging_id] = ($packagingConsumed[$product->carton_packaging_id] ?? 0) + $count;
            }
            if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
                $count = ceil($qty / $product->per_box) * $product->layers_per_box;
                $packagingConsumed[$product->layer_packaging_id] = ($packagingConsumed[$product->layer_packaging_id] ?? 0) + $count;
            }
        }
        $snapshot['packaging_used'] = $packagingConsumed;

        $snapshot['shoulder_records'] = ShoulderRecord::select('product_name', DB::raw('SUM(total) as total'))
            ->groupBy('product_name')->pluck('total', 'product_name')->toArray();
        $snapshot['waste_records'] = WasteMumRecord::select('product_name', DB::raw('SUM(amount) as total'))
            ->groupBy('product_name')->pluck('total', 'product_name')->toArray();

        return $snapshot;
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ محاسبه دلتا و اعمال idempotent
    //  stock_جدید = (stock_فعلی - imported_delta_sum) + دلتای_جدید
    // ═══════════════════════════════════════════════════════════
    private function applyInventoryDeltas($before, $after)
    {
        DB::beginTransaction();
        try {
            foreach (Product::where('status', 1)->get() as $product) {
                $id = $product->id;

                // ═══ موجودی انبار ═══
                $whDeltas = [
                    'import_tonneli_packaged' => [
                        'delta' => ($after['tonneli_packaged'][$id] ?? 0) - ($before['tonneli_packaged'][$id] ?? 0),
                        'description' => 'پخت کوره تونلی - بسته‌بندی‌شده',
                    ],
                    'import_shuttle_k1' => [
                        'delta' => ($after['shuttle_k1_packaged'][$id] ?? 0) - ($before['shuttle_k1_packaged'][$id] ?? 0),
                        'description' => 'پخت کوره ۱',
                    ],
                    'import_shuttle_k2' => [
                        'delta' => ($after['shuttle_k2_packaged'][$id] ?? 0) - ($before['shuttle_k2_packaged'][$id] ?? 0),
                        'description' => 'پخت کوره ۲ (۱۳۰۰)',
                    ],
                    'import_shuttle_k4' => [
                        'delta' => ($after['shuttle_k4_packaged'][$id] ?? 0) - ($before['shuttle_k4_packaged'][$id] ?? 0),
                        'description' => 'پخت کوره ۴',
                    ],
                    'import_packaging' => [
                        'delta' => ($after['shuttle_packaging_only'][$id] ?? 0) - ($before['shuttle_packaging_only'][$id] ?? 0),
                        'description' => 'بسته‌بندی محصول',
                    ],
                    'import_sale_formal' => [
                        'delta' => -(($after['formal_sales'][$id] ?? 0) - ($before['formal_sales'][$id] ?? 0)),
                        'description' => 'فروش رسمی',
                    ],
                    'import_sale_informal' => [
                        'delta' => -(($after['informal_sales'][$id] ?? 0) - ($before['informal_sales'][$id] ?? 0)),
                        'description' => 'فروش غیررسمی',
                    ],
                ];
                $this->applyDeltasWithLogs(
                    WarehouseInventory::firstOrCreate(['product_id' => $id]),
                    $whDeltas,
                    $product->id
                );

                // ═══ موجودی خام ═══
                $rawDeltas = [
                    'import_production' => [
                        'delta' => ($after['raw_production'][$id] ?? 0) - ($before['raw_production'][$id] ?? 0),
                        'description' => 'ثبت تولید',
                    ],
                    'import_tonneli_input' => [
                        'delta' => -(($after['raw_tonneli_input'][$id] ?? 0) - ($before['raw_tonneli_input'][$id] ?? 0)),
                        'description' => 'ورودی کوره تونلی',
                    ],
                    'import_shuttle_output' => [
                        'delta' => -(($after['raw_shuttle_output'][$id] ?? 0) - ($before['raw_shuttle_output'][$id] ?? 0)),
                        'description' => 'خروجی کوره شاتل',
                    ],
                ];
                $this->applyDeltasWithLogs(
                    RawInventory::firstOrCreate(['product_id' => $id]),
                    $rawDeltas,
                    $product->id
                );

                // ═══ موجودی ۱۳۰۰ ═══
                $g1300Deltas = [
                    'import_shuttle_k2_output' => [
                        'delta' => ($after['glaze1300_k2_output'][$id] ?? 0) - ($before['glaze1300_k2_output'][$id] ?? 0),
                        'description' => 'پخت کوره ۲ (۱۳۰۰)',
                    ],
                    'import_packaging_from_k2' => [
                        'delta' => -(($after['shuttle_k2_packaged'][$id] ?? 0) - ($before['shuttle_k2_packaged'][$id] ?? 0)),
                        'description' => 'بسته‌بندی از کوره ۲',
                    ],
                    'import_packaging_from_k4' => [
                        'delta' => -(($after['shuttle_k4_packaged'][$id] ?? 0) - ($before['shuttle_k4_packaged'][$id] ?? 0)),
                        'description' => 'بسته‌بندی از کوره ۴',
                    ],
                ];
                $this->applyDeltasWithLogs(
                    Glaze1300Inventory::firstOrCreate(['product_id' => $id]),
                    $g1300Deltas,
                    $product->id
                );

                // ═══ شانه شده ═══
                $shoulderDelta = ($after['shoulder_records'][$product->name] ?? 0) - ($before['shoulder_records'][$product->name] ?? 0);
                $inv = ShoulderInventory::firstOrCreate(['product_id' => $id]);
                $oldStock = (float) $inv->stock;
                $oldImportedSum = (float) ($inv->imported_delta_sum ?? 0);
                $baseStock = $oldStock - $oldImportedSum;
                $newStock = max(0, $baseStock + $shoulderDelta);

                if ($newStock != $oldStock) {
                    InventoryChangeLog::log($inv, 'stock', $oldStock, $newStock, 'adjust', $product->id, 'import_shoulder', 'شانه زنی');
                }
                $inv->stock = $newStock;
                $inv->imported_delta_sum = $shoulderDelta;
                $inv->save();

                // ═══ ضایعات موم ═══
                $wasteDelta = ($after['waste_records'][$product->name] ?? 0) - ($before['waste_records'][$product->name] ?? 0);
                $inv = WasteMumInventory::firstOrCreate(['product_id' => $id]);
                $oldStock = (float) $inv->stock;
                $oldImportedSum = (float) ($inv->imported_delta_sum ?? 0);
                $baseStock = $oldStock - $oldImportedSum;
                $newStock = max(0, $baseStock + $wasteDelta);

                if ($newStock != $oldStock) {
                    InventoryChangeLog::log($inv, 'stock', $oldStock, $newStock, 'adjust', $product->id, 'import_waste_mum', 'ضایعات موم');
                }
                $inv->stock = $newStock;
                $inv->imported_delta_sum = $wasteDelta;
                $inv->save();

                // ═══ موجودی موم ═══
                $waxDeltas = [
                    'import_shuttle_k3_mum' => [
                        'delta' => ($after['mum_k3_output'][$id] ?? 0) - ($before['mum_k3_output'][$id] ?? 0),
                        'description' => 'پخت کوره ۳ (موم)',
                    ],
                    'import_shoulder' => [
                        'delta' => -$shoulderDelta,
                        'description' => 'شانه زنی',
                    ],
                    'import_waste_mum' => [
                        'delta' => -$wasteDelta,
                        'description' => 'ضایعات موم',
                    ],
                ];
                $this->applyDeltasWithLogs(
                    WaxInventory::firstOrCreate(['product_id' => $id]),
                    $waxDeltas,
                    $product->id
                );
            }

            // ═══ مواد اولیه ═══
            $allMaterialIds = array_unique(array_merge(
                array_keys($before['raw_material_used']),
                array_keys($after['raw_material_used'])
            ));
            foreach ($allMaterialIds as $matId) {
                $delta = ($after['raw_material_used'][$matId] ?? 0) - ($before['raw_material_used'][$matId] ?? 0);
                $raw = RawMaterial::find($matId);
                if (!$raw) continue;

                $oldStock = (float) $raw->stock;
                $oldImportedSum = (float) ($raw->imported_delta_sum ?? 0);
                $baseStock = $oldStock - $oldImportedSum;
                $newImportedSum = -$delta; // مصرف = کاهش
                $newStock = max(0, $baseStock + $newImportedSum);

                if ($newStock != $oldStock) {
                    InventoryChangeLog::log($raw, 'stock', $oldStock, $newStock, 'adjust', null, 'import_material_making', 'مواد سازی');
                }

                $raw->stock = $newStock;
                $raw->imported_delta_sum = $newImportedSum;
                $raw->save();
            }

            // ═══ کارتن و لایه ═══
            foreach (Packaging::all() as $pkg) {
                $consumedAfter = $after['packaging_used'][$pkg->id] ?? 0;
                $consumedBefore = $before['packaging_used'][$pkg->id] ?? 0;
                $delta = $consumedAfter - $consumedBefore;

                $oldStock = (float) $pkg->stock;
                $oldImportedSum = (float) ($pkg->imported_delta_sum ?? 0);
                $baseStock = $oldStock - $oldImportedSum;
                $newImportedSum = -$delta;
                $newStock = max(0, $baseStock + $newImportedSum);

                if ($newStock != $oldStock) {
                    InventoryChangeLog::log($pkg, 'stock', $oldStock, $newStock, 'adjust', null, 'import_packaging_consumed', 'مصرف بسته‌بندی');
                }

                $pkg->stock = $newStock;
                $pkg->imported_delta_sum = $newImportedSum;
                $pkg->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('applyInventoryDeltas failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ اعمال دلتا با فرمول idempotent
    // ═══════════════════════════════════════════════════════════
    private function applyDeltasWithLogs($model, array $deltas, $productId = null)
    {
        $oldStock = (float) $model->stock;
        $oldImportedSum = (float) ($model->imported_delta_sum ?? 0);

        // مقدار پایه = موجودی قبل از هر ایمپورت (با حفظ تغییرات دستی)
        $baseStock = $oldStock - $oldImportedSum;

        // مجموع دلتای جدید
        $totalDelta = 0;
        foreach ($deltas as $source => $info) {
            $delta = $info['delta'] ?? 0;
            if (abs($delta) < 0.001) continue;
            $totalDelta += $delta;
        }

        $newStock = max(0, $baseStock + $totalDelta);

        // ثبت لاگ (اگه موجودی تغییر کرده)
        if ($newStock != $oldStock) {
            $running = $baseStock;
            foreach ($deltas as $source => $info) {
                $delta = $info['delta'] ?? 0;
                if (abs($delta) < 0.001) continue;
                $desc = $info['description'] ?? $source;
                $next = max(0, $running + $delta);
                InventoryChangeLog::log($model, 'stock', $running, $next, 'adjust', $productId, $source, $desc);
                $running = $next;
            }
        }

        // ذخیره
        $model->stock = $newStock;
        $model->imported_delta_sum = $totalDelta;
        $model->save();
    }

    private function importProductionsFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('تولید');
        if (!$sheet) return;
        $rows = $sheet->toArray();
        array_shift($rows);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    $year = (int) trim($row[0] ?? 0);
                    $month = (int) trim($row[1] ?? 0);
                    $day = (int) trim($row[2] ?? 0);
                    $operatorName = trim($row[3] ?? '');
                    $productName = trim($row[4] ?? '');
                    $pressNumber = trim($row[5] ?? '');
                    $quantity = (float) str_replace(',', '', trim($row[6] ?? 0));
                    $timeHours = (float) trim($row[7] ?? 0);
                    $waste = (float) trim($row[8] ?? 0);
                    $wasteReason = trim($row[9] ?? '');

                    if (empty($operatorName) || empty($productName) || $quantity <= 0) continue;

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    Jalalian::fromFormat('Y/m/d', $dateStr);

                    $operator = $this->getOrCreateOperator($operatorName);
                    $product = $this->findOrCreateProduct($productName);
                    $press = !empty($pressNumber) ? $this->getOrCreatePress($pressNumber) : null;

                    $production = Production::create([
                        'date' => $dateStr,
                        'operator_id' => $operator->id,
                        'press_id' => $press?->id,
                        'product_id' => $product->id,
                        'product_weight' => $product->weight,
                        'stage' => 'تولید',
                        'quantity' => $quantity,
                        'time_hours' => $timeHours,
                        'notes' => null,
                        'is_imported' => true,
                    ]);

                    if ($waste > 0 && !empty($wasteReason)) {
                        ProductionStop::create([
                            'production_id' => $production->id,
                            'type' => $this->detectStopType($wasteReason),
                            'hours' => $waste,
                        ]);
                    }
                } catch (\Exception $e) { /* ادامه */ }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
    }

    private function importTonneliFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('کوره تونلی');
        if (!$sheet) return;
        $rows = $sheet->toArray();
        array_shift($rows);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    $year = (int) trim($row[0] ?? 0);
                    $month = (int) trim($row[1] ?? 0);
                    $day = (int) trim($row[2] ?? 0);
                    $productName = trim($row[3] ?? '');
                    $inputQty = (float) str_replace(',', '', trim($row[4] ?? 0));
                    $outputQty = (float) str_replace(',', '', trim($row[5] ?? 0));
                    $isPackaged = trim($row[6] ?? '');

                    if (empty($productName) || ($inputQty <= 0 && $outputQty <= 0)) continue;

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);

                    $product = $this->findOrCreateProduct($productName);
                    $packaged = ($isPackaged == '1' || $isPackaged == 'بله') ? 1 : 0;

                    if ($inputQty > 0) {
                        $tonneliIn = TonneliFiring::create([
                            'date' => $jalaliDate->toCarbon(),
                            'is_imported' => true,
                        ]);
                        TonneliFiringItem::create([
                            'tonneli_firing_id' => $tonneliIn->id,
                            'product_id' => $product->id,
                            'input_quantity' => $inputQty,
                            'output_quantity' => 0,
                            'is_packaged' => 0,
                        ]);
                    }
                    if ($outputQty > 0) {
                        $tonneliOut = TonneliFiring::create([
                            'date' => $jalaliDate->toCarbon(),
                            'is_imported' => true,
                        ]);
                        TonneliFiringItem::create([
                            'tonneli_firing_id' => $tonneliOut->id,
                            'product_id' => $product->id,
                            'input_quantity' => 0,
                            'output_quantity' => $outputQty,
                            'is_packaged' => $packaged,
                        ]);
                    }
                } catch (\Exception $e) { /* ادامه */ }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
    }

    private function importShuttleFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('کوره شاتل');
        if (!$sheet) return;
        $rows = $sheet->toArray();
        array_shift($rows);

        $groups = [];
        foreach ($rows as $row) {
            try {
                $year = (int) trim($row[0] ?? 0);
                $month = (int) trim($row[1] ?? 0);
                $day = (int) trim($row[2] ?? 0);
                $kilnNumber = trim($row[3] ?? '');
                $firingType = trim($row[4] ?? '');
                $productName = trim($row[5] ?? '');
                $mainQty = (float) str_replace(',', '', trim($row[7] ?? 0));
                $isPackaged = trim($row[9] ?? '');

                if (empty($productName) || $mainQty <= 0) continue;

                $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);

                $product = $this->findOrCreateProduct($productName);
                $kilnType = $this->mapKilnNumberToType($kilnNumber, $firingType);
                $packaged = ($kilnType === 'packaging') ? 1 : (($isPackaged == '1' || $isPackaged == 'بله') ? 1 : 0);

                $firingSubtype = null;
                if ($kilnType === 'kiln_3') {
                    if (strpos($firingType, 'لعاب') !== false) $firingSubtype = 'glaze';
                    elseif (strpos($firingType, 'موم') !== false) $firingSubtype = 'mum';
                }

                $yearNum = $jalaliDate->getYear();
                $monthNum = $jalaliDate->getMonth();
                $dayNum = $jalaliDate->getDay();
                $groupKey = $yearNum . '-' . $monthNum . '-' . $dayNum . '-' . $kilnType;

                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = [
                        'year' => $yearNum, 'month' => $monthNum, 'day' => $dayNum,
                        'kiln_type' => $kilnType, 'date' => $jalaliDate->toCarbon(), 'items' => []
                    ];
                }
                $groups[$groupKey]['items'][] = [
                    'product_id' => $product->id,
                    'output_quantity' => $mainQty,
                    'is_packaged' => $packaged,
                    'firing_subtype' => $firingSubtype,
                ];
            } catch (\Exception $e) { /* ادامه */ }
        }

        usort($groups, function ($a, $b) {
            return strcmp($a['year'].'-'.$a['month'].'-'.$a['day'], $b['year'].'-'.$b['month'].'-'.$b['day']);
        });

        DB::beginTransaction();
        try {
            $monthlyCounters = [];
            foreach ($groups as $group) {
                $key = $group['year'].'-'.$group['month'].'-'.$group['kiln_type'];
                if (!isset($monthlyCounters[$key])) {
                    $maxNumber = ShuttleFiring::where('year', $group['year'])
                        ->where('month', $group['month'])
                        ->where('kiln_type', $group['kiln_type'])
                        ->max('firing_number') ?? 0;
                    $monthlyCounters[$key] = $maxNumber;
                }
                $monthlyCounters[$key]++;
                $newFiringNumber = $monthlyCounters[$key];

                foreach ($group['items'] as $item) {
                    ShuttleFiring::create([
                        'date' => $group['date'],
                        'kiln_type' => $group['kiln_type'],
                        'firing_subtype' => $item['firing_subtype'],
                        'product_id' => $item['product_id'],
                        'output_quantity' => $item['output_quantity'],
                        'firing_number' => $newFiringNumber,
                        'is_packaged' => $item['is_packaged'],
                        'year' => $group['year'],
                        'month' => $group['month'],
                        'day' => $group['day'],
                        'is_imported' => true,
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function importInformalSalesFromSpreadsheet($spreadsheet)
    {
        $sheetNames = ['غیر رسمی', 'غیررسمی', 'غیر رسمی فروش', 'غیررسمی فروش'];
        $sheet = null;
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) break;
        }
        if (!$sheet) return;

        $rows = $sheet->toArray();
        array_shift($rows);
        if (empty($rows)) return;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    if (empty(array_filter($row))) continue;
                    $row = array_pad($row, 10, '');

                    $year = (int) trim($row[0]);
                    $month = (int) trim($row[1]);
                    $day = (int) trim($row[2]);
                    $invoiceNumber = trim($row[3]);
                    $customerName = trim($row[4]);
                    $productName = trim($row[5]);
                    $quantity = (float) str_replace(',', '', trim($row[6]));
                    $unitPrice = (float) str_replace(',', '', trim($row[7]));
                    $totalPrice = (float) str_replace(',', '', trim($row[8]));
                    $paymentStatus = trim($row[9]);

                    if ($year < 1400 || $month < 1 || $month > 12 || $day < 1 || $day > 31 ||
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) continue;

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) { continue; }

                    $customer = Customer::firstOrCreate(['name' => $customerName], ['status' => 1]);
                    $product = $this->findOrCreateProduct($productName, 'SALE');
                    if ($totalPrice <= 0) $totalPrice = $quantity * $unitPrice;

                    $sale = InformalSale::where('year', $year)->where('number', $invoiceNumber)->first();
                    if (!$sale) {
                        $status = 'unpaid';
                        if ($paymentStatus == '1' || strtolower($paymentStatus) == 'بله' || strtolower($paymentStatus) == 'paid') {
                            $status = 'paid';
                        }
                        $sale = InformalSale::create([
                            'year' => $year, 'number' => $invoiceNumber, 'date' => $gregorianDate,
                            'customer_id' => $customer->id, 'customer_name' => $customer->name,
                            'total_price' => 0, 'status' => $status,
                        ]);
                    }
                    InformalSaleProduct::create([
                        'informal_sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                    $sale->total_price += $totalPrice;
                    $sale->save();
                } catch (\Exception $e) { /* ادامه */ }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in importInformalSalesFromSpreadsheet: ' . $e->getMessage());
        }
    }

    private function importFormalSalesFromSpreadsheet($spreadsheet)
    {
        $sheetNames = ['رسمی', 'فروش رسمی', 'رسمی فروش'];
        $sheet = null;
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) break;
        }
        if (!$sheet) {
            Log::warning('importFormalSales: هیچ شیتی با نام رسمی پیدا نشد.');
            return;
        }

        $rows = $sheet->toArray();
        array_shift($rows);
        if (empty($rows)) {
            Log::warning('importFormalSales: شیت رسمی خالیه.');
            return;
        }

        Log::info('importFormalSales: تعداد ردیف‌ها = ' . count($rows));

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::beginTransaction();
        try {
            $invoiceTotals = [];
            $invoiceTaxTotals = [];
            $invoiceWithTaxTotals = [];
            $invoiceStatus = [];

            $successCount = 0;
            $skipCount = 0;
            $errorCount = 0;

            foreach ($rows as $rowIndex => $row) {
                try {
                    if (empty(array_filter($row))) {
                        $skipCount++;
                        continue;
                    }
                    $col = array_pad($row, 14, '');

                    $year = (int) trim($col[0]);
                    $month = (int) trim($col[1]);
                    $day = (int) trim($col[2]);
                    $invoiceNumber = trim($col[3]);
                    $customerName = trim($col[4]);
                    $productName = trim($col[5]);
                    $quantity = (float) str_replace(',', '', trim($col[6]));
                    $unitPrice = (float) str_replace(',', '', trim($col[7]));
                    $priceAfterDiscount = (float) str_replace(',', '', trim($col[10]));
                    $taxAmount = (float) str_replace(',', '', trim($col[11]));
                    $totalWithTax = (float) str_replace(',', '', trim($col[12]));
                    $paymentStatus = isset($col[13]) ? trim($col[13]) : '';

                    if ($year < 1400 || $month < 1 || $month > 12 || $day < 1 || $day > 31 ||
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) {
                        $skipCount++;
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        $skipCount++;
                        continue;
                    }

                    $product = $this->findOrCreateProduct($productName, 'SALE');
                    if ($priceAfterDiscount <= 0) $priceAfterDiscount = $quantity * $unitPrice;

                    if (!isset($invoiceTotals[$invoiceNumber])) {
                        $invoiceTotals[$invoiceNumber] = 0;
                        $invoiceTaxTotals[$invoiceNumber] = 0;
                        $invoiceWithTaxTotals[$invoiceNumber] = 0;
                    }
                    $invoiceTotals[$invoiceNumber] += $priceAfterDiscount;
                    $invoiceTaxTotals[$invoiceNumber] += $taxAmount;
                    $invoiceWithTaxTotals[$invoiceNumber] += $totalWithTax;

                    if (!isset($invoiceStatus[$invoiceNumber])) {
                        if ($paymentStatus == '1' || strtolower($paymentStatus) == 'paid' || strtolower($paymentStatus) == 'پرداخت شده') {
                            $invoiceStatus[$invoiceNumber] = 'paid';
                        } else {
                            $invoiceStatus[$invoiceNumber] = 'pending';
                        }
                    }

                    $sale = Sale::where('invoice_number', $invoiceNumber)->first();
                    if (!$sale) {
                        $sale = Sale::create([
                            'invoice_number' => $invoiceNumber,
                            'date' => $gregorianDate,
                            'customer_name' => $customerName,
                            'invoice_id' => null,
                            'tax_percent' => 0,
                            'total_price' => 0,
                            'total_with_tax' => 0,
                            'status' => $invoiceStatus[$invoiceNumber],
                            'is_imported' => true,
                        ]);
                    }
                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('FormalSales row error: ' . $e->getMessage() . ' | RowIndex: ' . $rowIndex);
                }
            }

            foreach ($invoiceTotals as $invNum => $total) {
                $sale = Sale::where('invoice_number', $invNum)->first();
                if ($sale) {
                    $taxPercent = 0;
                    if ($total > 0 && isset($invoiceTaxTotals[$invNum])) {
                        $taxPercent = ($invoiceTaxTotals[$invNum] / $total) * 100;
                    }
                    $sale->total_price = $total;
                    $sale->tax_percent = round($taxPercent, 2);
                    $sale->total_with_tax = $invoiceWithTaxTotals[$invNum] ?? $total;
                    if (isset($invoiceStatus[$invNum])) $sale->status = $invoiceStatus[$invNum];
                    $sale->save();
                }
            }

            DB::commit();
            Log::info("importFormalSales خلاصه: موفق={$successCount} | رد شده={$skipCount} | خطا={$errorCount}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in importFormalSalesFromSpreadsheet: ' . $e->getMessage());
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function importShoulderFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('شانه زنی');
        if (!$sheet) return;

        $rows = $sheet->toArray();
        array_shift($rows);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;

                $year = (int) trim($row[0] ?? 0);
                $month = (int) trim($row[1] ?? 0);
                $day = (int) trim($row[2] ?? 0);
                $name = trim($row[3] ?? '');
                $productName = trim($row[4] ?? '');
                $cartonCount = (int) str_replace(',', '', trim($row[5] ?? 0));
                $perCarton = (int) str_replace(',', '', trim($row[6] ?? 0));
                $total = (int) str_replace(',', '', trim($row[7] ?? 0));
                $shoulder = (int) str_replace(',', '', trim($row[8] ?? 0));

                if (empty($productName)) continue;

                if ($name == 'ضایعات موم') {
                    WasteMumRecord::create([
                        'year' => $year, 'month' => $month, 'day' => $day,
                        'product_name' => $productName, 'amount' => $total,
                    ]);
                } else {
                    if ($total <= 0) continue;
                    ShoulderRecord::create([
                        'year' => $year, 'month' => $month, 'day' => $day,
                        'name' => $name, 'product_name' => $productName,
                        'carton_count' => $cartonCount, 'per_carton' => $perCarton,
                        'total' => $total, 'shoulder' => $shoulder,
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
    }

    private function importMaterialMakingFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('مواد سازی');
        if (!$sheet) return;

        $rows = $sheet->toArray();
        array_shift($rows);

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    if (empty(array_filter($row))) continue;

                    $year = (int) trim($row[0] ?? 0);
                    $month = (int) trim($row[1] ?? 0);
                    $day = (int) trim($row[2] ?? 0);
                    $name = trim($row[3] ?? '');
                    $formulaName = trim($row[4] ?? '');
                    $quantity = (float) str_replace(',', '', trim($row[5] ?? 0));
                    $millWeightKg = (float) str_replace(',', '', trim($row[6] ?? 0));

                    if ($year < 1400 || $month < 1 || $month > 12 || $day < 1 || $day > 31) continue;
                    if (empty($formulaName) || $quantity <= 0 || $millWeightKg <= 0) continue;

                    $millWeightGram = $millWeightKg * 1000;

                    MaterialMaking::create([
                        'year' => $year, 'month' => $month, 'day' => $day,
                        'name' => $name, 'material' => $formulaName,
                        'quantity' => $quantity, 'mill_weight' => $millWeightGram,
                        'is_imported' => true,
                    ]);
                } catch (\Exception $e) {
                    Log::warning("خطا در ردیف مواد سازی: " . $e->getMessage());
                }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
    }

    private $operatorsCache = [];
    private $productsCache = [];
    private $pressesCache = [];

    private function getOrCreateOperator($name)
    {
        $cleanName = trim($name);
        if (!isset($this->operatorsCache[$cleanName])) {
            $this->operatorsCache[$cleanName] = Operator::firstOrCreate(
                ['name' => $cleanName],
                ['status' => 1]
            );
        }
        return $this->operatorsCache[$cleanName];
    }

    private function findOrCreateProduct($name, $codePrefix = 'IMP')
    {
        $cleanName = trim($name);
        $cacheKey = $cleanName;

        if (isset($this->productsCache[$cacheKey])) {
            return $this->productsCache[$cacheKey];
        }
        $product = Product::where('name', $cleanName)->first();
        if ($product) {
            $this->productsCache[$cacheKey] = $product;
            return $product;
        }
        $alias = ProductAlias::where('alias', $cleanName)->first();
        if ($alias) {
            $product = $alias->product;
            $this->productsCache[$cacheKey] = $product;
            return $product;
        }
        $product = Product::create([
            'code' => $codePrefix . '-' . time() . '-' . rand(100, 999),
            'name' => $cleanName,
            'unit_id' => 1,
            'status' => 1,
            'weight' => 0,
            'per_box' => 0,
            'layers_per_box' => 0,
            'firing_process' => 'both',
        ]);
        $this->productsCache[$cacheKey] = $product;
        return $product;
    }

    private function findProduct($name)
    {
        $cleanName = trim($name);
        if (isset($this->productsCache[$cleanName])) {
            return $this->productsCache[$cleanName];
        }
        $product = Product::where('name', $cleanName)->first();
        if ($product) {
            $this->productsCache[$cleanName] = $product;
            return $product;
        }
        $alias = ProductAlias::where('alias', $cleanName)->first();
        if ($alias) {
            $product = $alias->product;
            $this->productsCache[$cleanName] = $product;
            return $product;
        }
        return null;
    }

    private function getOrCreatePress($number)
    {
        $name = 'پرس ' . trim($number);
        if (!isset($this->pressesCache[$name])) {
            $this->pressesCache[$name] = Press::firstOrCreate(
                ['name' => $name],
                ['status' => 1]
            );
        }
        return $this->pressesCache[$name];
    }

    private function mapKilnNumberToType($kilnNumber, $firingType = null)
    {
        $kilnNumber = trim($kilnNumber);
        if (is_numeric($kilnNumber)) {
            $num = (int)$kilnNumber;
            if ($num >= 1 && $num <= 4) return 'kiln_' . $num;
        }
        if (strtolower($kilnNumber) === 'بسته‌بندی' || strtolower($kilnNumber) === 'packaging') {
            return 'packaging';
        }
        if ($firingType) {
            if (strpos($firingType, 'معمولی') !== false) return 'kiln_1';
            if (strpos($firingType, '1300') !== false) return 'kiln_2';
            if (strpos($firingType, 'لعاب') !== false || strpos($firingType, 'موم') !== false) return 'kiln_3';
        }
        return 'kiln_1';
    }

    private function detectStopType($reason)
    {
        $reason = trim($reason);
        if (strpos($reason, 'قالب') !== false || strpos($reason, 'تعویض') !== false) return 'تعویض قالب';
        if (strpos($reason, 'خرابی') !== false || strpos($reason, 'ماشین') !== false) return 'خرابی ماشین';
        return 'سایر';
    }
}