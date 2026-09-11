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
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    // ============================================================
    //  صفحه اصلی
    // ============================================================
    public function index()
    {
        return view('import.index');
    }

    // ============================================================
    //  ✅ واردات خودکار (تنها متد اصلی)
    // ============================================================
    public function importFromPath()
    {
        $filePath = env('EXCEL_FILE_PATH');

        if (empty($filePath) || !file_exists($filePath)) {
            return redirect()->route('import.index')
                ->withErrors(['file' => 'مسیر فایل اکسل در فایل .env تنظیم نشده یا فایل وجود ندارد.']);
        }

        $errors = [];
        $anySuccess = false;

        try {
            set_time_limit(0);

            // عکس قبل از واردات
            $beforeSnapshot = $this->takeInventorySnapshot();

            // پاکسازی جداول
            DB::statement('DELETE FROM production_stops');
            DB::statement('DELETE FROM productions');
            DB::statement('DELETE FROM tonneli_firing_items');
            DB::statement('DELETE FROM tonneli_firings');
            DB::statement('DELETE FROM shuttle_firings');
            DB::statement('DELETE FROM material_makings');

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            $steps = [
                'importProductionsFromSpreadsheet'    => 'تولید',
                'importTonneliFromSpreadsheet'        => 'کوره تونلی',
                'importShuttleFromSpreadsheet'        => 'کوره شاتل',
                'importInformalSalesFromSpreadsheet'  => 'فروش غیررسمی',
                'importFormalSalesFromSpreadsheet'    => 'فروش رسمی',
                'importShoulderFromSpreadsheet'       => 'شانه زنی',
                'importMaterialMakingFromSpreadsheet' => 'مواد سازی',
            ];

            foreach ($steps as $method => $label) {
                try {
                    $this->$method($spreadsheet);
                    $anySuccess = true;
                } catch (\Exception $e) {
                    $errors[] = "خطا در برگه {$label}: " . $e->getMessage();
                    \Log::error("{$method} failed: " . $e->getMessage());
                }
            }

            // عکس بعد از واردات و اعمال تفاضل
            $afterSnapshot = $this->takeInventorySnapshot();
            $this->applyInventoryDeltas($beforeSnapshot, $afterSnapshot);

        } catch (\Exception $e) {
            return redirect()->route('import.index')
                ->withErrors(['file' => 'خطا در خواندن فایل: ' . $e->getMessage()]);
        }

        if ($anySuccess) {
            $message = '✅ واردات خودکار با موفقیت انجام شد و موجودی‌ها به‌روز شدند.';
            if (!empty($errors)) {
                $message .= ' ⚠️ اما برخی خطاها رخ داد: ' . implode(' | ', $errors);
            }
            return redirect()->route('import.index')->with('success', $message);
        } else {
            return redirect()->route('import.index')
                ->withErrors(['file' => '❌ هیچ برگه‌ای با موفقیت وارد نشد. خطاها: ' . implode(' | ', $errors)]);
        }
    }

    // ============================================================
    //  محاسبه موجودی خام
    // ============================================================
    private function calculateRawStock($product)
    {
        $production = Production::where('product_id', $product->id)
            ->whereNotNull('press_id')
            ->sum('quantity');

        if ($production == 0) return 0;

        if ($product->isInjection()) {
            $shuttleMum = ShuttleFiring::where('product_id', $product->id)
                ->where('kiln_type', 'kiln_3')
                ->where('firing_subtype', 'mum')
                ->sum('output_quantity');

            return max(0, $production - $shuttleMum);
        }

        $tonneliInput = TonneliFiringItem::where('product_id', $product->id)
            ->sum('input_quantity');

        $shuttleOutput = 0;
        if ($product->name !== 'بلسن') {
            $shuttleOutput = ShuttleFiring::where('product_id', $product->id)
                ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
                ->sum('output_quantity');
        }

        $childOutput = 0;
        foreach ($product->children as $child) {
            $childOutput += TonneliFiringItem::where('product_id', $child->id)
                ->where('input_quantity', '>', 0)
                ->sum('input_quantity');

            if ($child->name !== 'بلسن') {
                $childOutput += ShuttleFiring::where('product_id', $child->id)
                    ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
                    ->sum('output_quantity');
            }
        }

        return max(0, $production - $tonneliInput - $shuttleOutput - $childOutput);
    }

    // ============================================================
    //  عکس گرفتن از وضعیت فعلی
    // ============================================================
    private function takeInventorySnapshot()
    {
        $snapshot = [
            'warehouse_produced'  => [],
            'formal_sales'        => [],
            'informal_sales'      => [],
            'glaze1300_produced'  => [],
            'glaze1300_packaged'  => [],
            'mum_produced'        => [],
            'shoulder_records'    => [],
            'waste_records'       => [],
            'raw_calculated'      => [],
            'raw_material_used'   => [],
            'packaging_used'      => [],
        ];

        $formalSales = SaleProduct::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();
        $informalSales = InformalSaleProduct::select('product_id', DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $tonneliPackaged = TonneliFiringItem::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('is_packaged', 1)->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $shuttlePackaged = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('is_packaged', 1)->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_4'])
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $glaze1300Output = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_2')->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $packagedK2 = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_2')->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();
        $packagedK4 = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_4')->where('is_packaged', 1)
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        $mumOutput = ShuttleFiring::select('product_id', DB::raw('SUM(output_quantity) as total'))
            ->where('kiln_type', 'kiln_3')->where('firing_subtype', 'mum')
            ->groupBy('product_id')->pluck('total', 'product_id')->toArray();

        // مواد اولیه مصرفی
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

        // کارتن و لایه مصرفی
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

        $shoulderRecords = ShoulderRecord::select('product_name', DB::raw('SUM(total) as total'))
            ->groupBy('product_name')->pluck('total', 'product_name')->toArray();
        $wasteRecords = WasteMumRecord::select('product_name', DB::raw('SUM(amount) as total'))
            ->groupBy('product_name')->pluck('total', 'product_name')->toArray();

        foreach (Product::where('status', 1)->get() as $product) {
            $id = $product->id;
            $snapshot['warehouse_produced'][$id] = ($tonneliPackaged[$id] ?? 0) + ($shuttlePackaged[$id] ?? 0);
            $snapshot['formal_sales'][$id]       = $formalSales[$id] ?? 0;
            $snapshot['informal_sales'][$id]     = $informalSales[$id] ?? 0;
            $snapshot['glaze1300_produced'][$id] = $glaze1300Output[$id] ?? 0;
            $snapshot['glaze1300_packaged'][$id] = ($packagedK2[$id] ?? 0) + ($packagedK4[$id] ?? 0);
            $snapshot['mum_produced'][$id]       = $mumOutput[$id] ?? 0;
            $snapshot['shoulder_records'][$id]   = $shoulderRecords[$product->name] ?? 0;
            $snapshot['waste_records'][$id]      = $wasteRecords[$product->name] ?? 0;
            $snapshot['raw_calculated'][$id]     = $this->calculateRawStock($product);
        }

        $snapshot['raw_material_used'] = $rawConsumed;
        $snapshot['packaging_used']    = $packagingConsumed;

        return $snapshot;
    }

    // ============================================================
    //  اعمال تفاضل روی موجودی‌ها
    // ============================================================
    private function applyInventoryDeltas($before, $after)
    {
        DB::beginTransaction();
        try {
            foreach (Product::where('status', 1)->get() as $product) {
                $id = $product->id;

                // ۱) موجودی انبار
                $whDelta =
                    (($after['warehouse_produced'][$id] ?? 0) - ($before['warehouse_produced'][$id] ?? 0))
                    - (($after['formal_sales'][$id] ?? 0) - ($before['formal_sales'][$id] ?? 0))
                    - (($after['informal_sales'][$id] ?? 0) - ($before['informal_sales'][$id] ?? 0));

                if ($whDelta != 0) {
                    $inv = WarehouseInventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $whDelta);
                    $inv->save();
                }

                // ۲) موجودی خام
                $rawDelta = ($after['raw_calculated'][$id] ?? 0) - ($before['raw_calculated'][$id] ?? 0);
                if ($rawDelta != 0) {
                    $inv = RawInventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $rawDelta);
                    $inv->save();
                }

                // ۳) موجودی ۱۳۰۰ درجه
                $g1300Delta =
                    (($after['glaze1300_produced'][$id] ?? 0) - ($before['glaze1300_produced'][$id] ?? 0))
                    - (($after['glaze1300_packaged'][$id] ?? 0) - ($before['glaze1300_packaged'][$id] ?? 0));

                if ($g1300Delta != 0) {
                    $inv = Glaze1300Inventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $g1300Delta);
                    $inv->save();
                }

                // ۴) شانه شده
                $shoulderDelta = ($after['shoulder_records'][$id] ?? 0) - ($before['shoulder_records'][$id] ?? 0);
                if ($shoulderDelta != 0) {
                    $inv = ShoulderInventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $shoulderDelta);
                    $inv->save();
                }

                // ۵) ضایعات موم
                $wasteDelta = ($after['waste_records'][$id] ?? 0) - ($before['waste_records'][$id] ?? 0);
                if ($wasteDelta != 0) {
                    $inv = WasteMumInventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $wasteDelta);
                    $inv->save();
                }

                // ۶) موجودی موم
                $waxDelta =
                    (($after['mum_produced'][$id] ?? 0) - ($before['mum_produced'][$id] ?? 0))
                    - $shoulderDelta
                    - $wasteDelta;

                if ($waxDelta != 0) {
                    $inv = WaxInventory::firstOrCreate(['product_id' => $id]);
                    $inv->stock = max(0, $inv->stock + $waxDelta);
                    $inv->save();
                }
            }

            // ۷) مواد اولیه
            $allMaterialIds = array_unique(array_merge(
                array_keys($before['raw_material_used']),
                array_keys($after['raw_material_used'])
            ));
            foreach ($allMaterialIds as $matId) {
                $delta = ($after['raw_material_used'][$matId] ?? 0) - ($before['raw_material_used'][$matId] ?? 0);
                if ($delta != 0) {
                    $raw = RawMaterial::find($matId);
                    if ($raw) {
                        $raw->stock = max(0, $raw->stock - $delta);
                        $raw->save();
                    }
                }
            }

            // ۸) کارتن و لایه
            $allPackagingIds = array_unique(array_merge(
                array_keys($before['packaging_used']),
                array_keys($after['packaging_used'])
            ));
            foreach ($allPackagingIds as $pkgId) {
                $delta = ($after['packaging_used'][$pkgId] ?? 0) - ($before['packaging_used'][$pkgId] ?? 0);
                if ($delta != 0) {
                    $pkg = Packaging::find($pkgId);
                    if ($pkg) {
                        $pkg->stock = max(0, $pkg->stock - $delta);
                        $pkg->save();
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('applyInventoryDeltas failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // ============================================================
    //  واردات برگه‌ها از Spreadsheet
    // ============================================================

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
                        $tonneliIn = TonneliFiring::create(['date' => $jalaliDate->toCarbon()]);
                        TonneliFiringItem::create([
                            'tonneli_firing_id' => $tonneliIn->id,
                            'product_id' => $product->id,
                            'input_quantity' => $inputQty,
                            'output_quantity' => 0,
                            'is_packaged' => 0,
                        ]);
                    }
                    if ($outputQty > 0) {
                        $tonneliOut = TonneliFiring::create(['date' => $jalaliDate->toCarbon()]);
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
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
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

        DB::statement('DELETE FROM informal_sale_products');
        DB::statement('DELETE FROM informal_sales');

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
                    $product = $this->findProduct($productName);
                    if (!$product) continue;
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
            \Log::error('Error in importInformalSalesFromSpreadsheet: ' . $e->getMessage());
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
        if (!$sheet) return;

        $rows = $sheet->toArray();
        array_shift($rows);
        if (empty($rows)) return;

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DELETE FROM sale_products');
        DB::statement('DELETE FROM sales');

        DB::beginTransaction();
        try {
            $invoiceTotals = [];
            $invoiceTaxTotals = [];
            $invoiceWithTaxTotals = [];
            $invoiceStatus = [];

            foreach ($rows as $row) {
                try {
                    if (empty(array_filter($row))) continue;
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
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) continue;

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) { continue; }

                    $product = $this->findProduct($productName);
                    if (!$product) continue;
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
                        ]);
                    }
                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ]);
                } catch (\Exception $e) { /* ادامه */ }
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
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in importFormalSalesFromSpreadsheet: ' . $e->getMessage());
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
            ShoulderRecord::truncate();
            WasteMumRecord::truncate();

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
                    ]);
                } catch (\Exception $e) {
                    \Log::warning("خطا در ردیف مواد سازی: " . $e->getMessage());
                }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); throw $e; }
    }

    // ============================================================
    //  متدهای کمکی
    // ============================================================

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

    private function findOrCreateProduct($name)
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
        $product = Product::create([
            'code' => 'IMP-' . time() . '-' . rand(100, 999),
            'name' => $cleanName,
            'unit_id' => 1,
            'status' => 1,
            'cavities' => 0,
            'weight' => 0,
            'per_box' => 0,
            'layers_per_box' => 0,
            'firing_process' => 'tonneli',
        ]);
        $this->productsCache[$cleanName] = $product;
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