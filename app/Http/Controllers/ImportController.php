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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    // ============================================================
    //  صفحه اصلی واردات
    // ============================================================
    public function index()
    {
        return view('import.index');
    }

    // ============================================================
    //  ✅ واردات خودکار از مسیر (اصلاح‌شده)
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

            DB::statement('DELETE FROM production_stops');
            DB::statement('DELETE FROM productions');
            DB::statement('DELETE FROM tonneli_firing_items');
            DB::statement('DELETE FROM tonneli_firings');
            DB::statement('DELETE FROM shuttle_firings');

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            try {
                $this->importProductionsFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه تولید: ' . $e->getMessage();
                \Log::error('importProductionsFromSpreadsheet failed: ' . $e->getMessage());
            }

            try {
                $this->importTonneliFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه کوره تونلی: ' . $e->getMessage();
                \Log::error('importTonneliFromSpreadsheet failed: ' . $e->getMessage());
            }

            try {
                $this->importShuttleFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه کوره شاتل: ' . $e->getMessage();
                \Log::error('importShuttleFromSpreadsheet failed: ' . $e->getMessage());
            }

            try {
                $this->importInformalSalesFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه فروش غیررسمی: ' . $e->getMessage();
                \Log::error('importInformalSalesFromSpreadsheet failed: ' . $e->getMessage());
            }

            try {
                $this->importFormalSalesFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه فروش رسمی: ' . $e->getMessage();
                \Log::error('importFormalSalesFromSpreadsheet failed: ' . $e->getMessage());
            }

            try {
                $this->importShoulderFromSpreadsheet($spreadsheet);
                $anySuccess = true;
            } catch (\Exception $e) {
                $errors[] = 'خطا در برگه شانه زنی: ' . $e->getMessage();
                \Log::error('importShoulderFromSpreadsheet failed: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            return redirect()->route('import.index')
                ->withErrors(['file' => 'خطا در خواندن فایل: ' . $e->getMessage()]);
        }

        try {
            Artisan::call('raw-material:fix-stock');
            Artisan::call('wax:fix-stock');
            Artisan::call('glaze1300:fix-stock');
            Artisan::call('warehouse:fix-stock');
            Artisan::call('shoulder:fix-stock');
            Artisan::call('wastemum:fix-stock');
            Artisan::call('wax:fix-stock');
            Artisan::call('packaging:fix-stock'); // ✅ اضافه شد
        } catch (\Exception $e) {
            $errors[] = 'خطا در به‌روزرسانی موجودی‌ها: ' . $e->getMessage();
            \Log::error('Artisan commands failed: ' . $e->getMessage());
        }

        if ($anySuccess) {
            $message = '✅ واردات خودکار با موفقیت انجام شد.';
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
    //  برگه تولید (آپلود دستی)
    // ============================================================
    public function importProductions(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getSheetByName('تولید');

        if (!$sheet) {
            return back()->withErrors(['file' => 'برگه "تولید" در فایل یافت نشد.']);
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        $count = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
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

                    if (empty($operatorName) || empty($productName) || $quantity <= 0) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": داده‌های ضروری کامل نیستند.";
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        Jalalian::fromFormat('Y/m/d', $dateStr);
                    } catch (\Exception $e) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": تاریخ {$dateStr} نامعتبر است.";
                        continue;
                    }

                    $operator = $this->getOrCreateOperator($operatorName);
                    $product = $this->findOrCreateProduct($productName);
                    $press = !empty($pressNumber) ? $this->getOrCreatePress($pressNumber) : null;

                    $productWeight = $product ? $product->weight : null;

                    $production = Production::create([
                        'date' => $dateStr,
                        'operator_id' => $operator->id,
                        'press_id' => $press?->id,
                        'product_id' => $product->id,
                        'product_weight' => $productWeight,
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

                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در حین ذخیره‌سازی: ' . $e->getMessage()]);
        }

        Artisan::call('raw-material:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('glaze1300:fix-stock');
        Artisan::call('warehouse:fix-stock');
        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');

        $message = "✅ {$count} رکورد تولید با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه کوره تونلی (آپلود دستی) - اصلاح‌شده
    // ============================================================
    public function importTonneli(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getSheetByName('کوره تونلی');

        if (!$sheet) {
            return back()->withErrors(['file' => 'برگه "کوره تونلی" در فایل یافت نشد.']);
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        $count = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                try {
                    $year = (int) trim($row[0] ?? 0);
                    $month = (int) trim($row[1] ?? 0);
                    $day = (int) trim($row[2] ?? 0);
                    $productName = trim($row[3] ?? '');
                    $inputQty = (float) str_replace(',', '', trim($row[4] ?? 0));
                    $outputQty = (float) str_replace(',', '', trim($row[5] ?? 0));
                    $isPackaged = trim($row[6] ?? '');

                    if (empty($productName) || ($inputQty <= 0 && $outputQty <= 0)) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": داده‌های ضروری کامل نیستند.";
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                    } catch (\Exception $e) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": تاریخ {$dateStr} نامعتبر است.";
                        continue;
                    }

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

                        // ✅ کسر کارتن و لایه در صورت بسته‌بندی
                        if ($packaged) {
                            $product->subtractPackaging($outputQty);
                        }
                    }

                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در حین ذخیره‌سازی: ' . $e->getMessage()]);
        }

        Artisan::call('raw-material:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('glaze1300:fix-stock');
        Artisan::call('warehouse:fix-stock');
        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('packaging:fix-stock');

        $message = "✅ {$count} رکورد کوره تونلی با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه کوره شاتل (آپلود دستی) - اصلاح‌شده
    // ============================================================
    public function importShuttle(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getSheetByName('کوره شاتل');

        if (!$sheet) {
            return back()->withErrors(['file' => 'برگه "کوره شاتل" در فایل یافت نشد.']);
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        $groups = [];
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            try {
                $year = (int) trim($row[0] ?? 0);
                $month = (int) trim($row[1] ?? 0);
                $day = (int) trim($row[2] ?? 0);
                $kilnNumber = trim($row[3] ?? '');
                $firingType = trim($row[4] ?? '');
                $productName = trim($row[5] ?? '');
                $mainQty = (float) str_replace(',', '', trim($row[7] ?? 0));
                $isPackaged = trim($row[9] ?? '');

                if (empty($productName) || $mainQty <= 0) {
                    $errors[] = "ردیف " . ($rowIndex + 2) . ": داده‌های ضروری کامل نیستند.";
                    continue;
                }

                $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);

                $product = $this->findOrCreateProduct($productName);
                $kilnType = $this->mapKilnNumberToType($kilnNumber, $firingType);

                $packaged = ($kilnType === 'packaging') ? 1 : (($isPackaged == '1' || $isPackaged == 'بله') ? 1 : 0);

                $firingSubtype = null;
                if ($kilnType === 'kiln_3') {
                    if (strpos($firingType, 'لعاب') !== false) {
                        $firingSubtype = 'glaze';
                    } elseif (strpos($firingType, 'موم') !== false) {
                        $firingSubtype = 'mum';
                    }
                }

                $yearNum = $jalaliDate->getYear();
                $monthNum = $jalaliDate->getMonth();
                $dayNum = $jalaliDate->getDay();

                $groupKey = $yearNum . '-' . $monthNum . '-' . $dayNum . '-' . $kilnType;

                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = [
                        'year' => $yearNum,
                        'month' => $monthNum,
                        'day' => $dayNum,
                        'kiln_type' => $kilnType,
                        'date' => $jalaliDate->toCarbon(),
                        'items' => []
                    ];
                }

                $groups[$groupKey]['items'][] = [
                    'product_id' => $product->id,
                    'output_quantity' => $mainQty,
                    'is_packaged' => $packaged,
                    'firing_subtype' => $firingSubtype,
                ];

            } catch (\Exception $e) {
                $errors[] = "ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }

        usort($groups, function ($a, $b) {
            return strcmp($a['year'] . '-' . $a['month'] . '-' . $a['day'],
                          $b['year'] . '-' . $b['month'] . '-' . $b['day']);
        });

        $count = 0;
        DB::beginTransaction();

        try {
            $monthlyCounters = [];

            foreach ($groups as $group) {
                $key = $group['year'] . '-' . $group['month'] . '-' . $group['kiln_type'];

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

                    // ✅ کسر کارتن و لایه در صورت بسته‌بندی
                    if ($item['is_packaged']) {
                        $product = Product::find($item['product_id']);
                        if ($product) {
                            $product->subtractPackaging($item['output_quantity']);
                        }
                    }
                }

                $count += count($group['items']);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در حین ذخیره‌سازی: ' . $e->getMessage()]);
        }

        Artisan::call('raw-material:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('glaze1300:fix-stock');
        Artisan::call('warehouse:fix-stock');
        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('packaging:fix-stock');

        $message = "✅ {$count} رکورد کوره شاتل در " . count($groups) . " پخت با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه فروش غیررسمی (آپلود دستی)
    // ============================================================
    public function importInformalSales(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());

        $sheetNames = ['غیر رسمی', 'غیررسمی', 'غیر رسمی فروش', 'غیررسمی فروش'];
        $sheet = null;
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) break;
        }

        if (!$sheet) {
            $allSheets = [];
            foreach ($spreadsheet->getAllSheets() as $s) {
                $allSheets[] = $s->getTitle();
            }
            return back()->withErrors(['file' => 'برگه "غیر رسمی" پیدا نشد. برگه‌ها: ' . implode(', ', $allSheets)]);
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        if (empty($rows)) {
            return back()->withErrors(['file' => 'فایل خالی است.']);
        }

        $count = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                try {
                    if (empty(array_filter($row))) {
                        continue;
                    }

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
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": داده‌های ضروری کامل نیستند.";
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": تاریخ {$dateStr} نامعتبر است.";
                        continue;
                    }

                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        ['status' => 1]
                    );

                    $product = $this->findProduct($productName);
                    if (!$product) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": محصول '{$productName}' پیدا نشد. لطفاً ابتدا محصول را تعریف کنید.";
                        continue;
                    }

                    if ($totalPrice <= 0) {
                        $totalPrice = $quantity * $unitPrice;
                    }

                    $sale = InformalSale::where('year', $year)
                        ->where('number', $invoiceNumber)
                        ->first();

                    if (!$sale) {
                        $status = 'pending';
                        if ($paymentStatus == '1' ||
                            strtolower($paymentStatus) == 'بله' ||
                            strtolower($paymentStatus) == 'paid') {
                            $status = 'paid';
                        }

                        $sale = InformalSale::create([
                            'year' => $year,
                            'number' => $invoiceNumber,
                            'date' => $gregorianDate,
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'total_price' => 0,
                            'status' => $status,
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

                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }

        Artisan::call('raw-material:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('glaze1300:fix-stock');
        Artisan::call('warehouse:fix-stock');
        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');

        $message = "✅ {$count} آیتم فروش غیررسمی با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
            }
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه فروش رسمی (آپلود دستی)
    // ============================================================
    public function importFormalSales(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());

        $sheetNames = ['رسمی', 'فروش رسمی', 'رسمی فروش'];
        $sheet = null;
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if ($sheet) break;
        }

        if (!$sheet) {
            $allSheets = [];
            foreach ($spreadsheet->getAllSheets() as $s) {
                $allSheets[] = $s->getTitle();
            }
            return back()->withErrors(['file' => 'برگه "رسمی" پیدا نشد. برگه‌ها: ' . implode(', ', $allSheets)]);
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        if (empty($rows)) {
            return back()->withErrors(['file' => 'فایل خالی است.']);
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DELETE FROM sale_products');
        DB::statement('DELETE FROM sales');

        $count = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $invoiceTotals = [];
            $invoiceTaxTotals = [];
            $invoiceWithTaxTotals = [];
            $invoiceStatus = [];

            foreach ($rows as $rowIndex => $row) {
                try {
                    if (empty(array_filter($row))) {
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
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": داده‌های ضروری کامل نیستند.";
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": تاریخ {$dateStr} نامعتبر است.";
                        continue;
                    }

                    $product = $this->findProduct($productName);
                    if (!$product) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": محصول '{$productName}' پیدا نشد. لطفاً ابتدا محصول را تعریف کنید.";
                        continue;
                    }

                    if ($priceAfterDiscount <= 0) {
                        $priceAfterDiscount = $quantity * $unitPrice;
                    }

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

                    $count++;

                } catch (\Exception $e) {
                    $errors[] = "ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
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
                    if (isset($invoiceStatus[$invNum])) {
                        $sale->status = $invoiceStatus[$invNum];
                    }
                    $sale->save();

                    foreach ($sale->products as $product) {
                        $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                        $this->decreaseStockDB($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        Artisan::call('raw-material:fix-stock');
        Artisan::call('wax:fix-stock');
        Artisan::call('glaze1300:fix-stock');
        Artisan::call('warehouse:fix-stock');
        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');

        $message = "✅ {$count} آیتم فروش رسمی با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
            }
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه شانه زنی (آپلود دستی)
    // ============================================================
    public function importShoulder(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());

        $sheet = $spreadsheet->getSheetByName('شانه زنی');
        if (!$sheet) {
            return back()->withErrors(['file' => 'برگه "شانه زنی" در فایل یافت نشد.']);
        }

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
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'product_name' => $productName,
                        'amount' => $total,
                    ]);
                } else {
                    if ($total <= 0) continue;
                    ShoulderRecord::create([
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'name' => $name,
                        'product_name' => $productName,
                        'carton_count' => $cartonCount,
                        'per_carton' => $perCarton,
                        'total' => $total,
                        'shoulder' => $shoulder,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در ذخیره‌سازی: ' . $e->getMessage()]);
        }

        Artisan::call('shoulder:fix-stock');
        Artisan::call('wastemum:fix-stock');
        Artisan::call('wax:fix-stock');

        return redirect()->route('import.index')
            ->with('success', '✅ برگه شانه زنی با موفقیت وارد شد و موجودی‌ها به‌روز شد.');
    }

    // ============================================================
    //  متدهای خصوصی
    // ============================================================

    private function importShoulderFromSpreadsheet($spreadsheet)
    {
        $sheet = $spreadsheet->getSheetByName('شانه زنی');
        if (!$sheet) {
            return;
        }

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
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'product_name' => $productName,
                        'amount' => $total,
                    ]);
                } else {
                    if ($total <= 0) continue;
                    ShoulderRecord::create([
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                        'name' => $name,
                        'product_name' => $productName,
                        'carton_count' => $cartonCount,
                        'per_carton' => $perCarton,
                        'total' => $total,
                        'shoulder' => $shoulder,
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

        if (!$sheet) {
            return;
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        if (empty($rows)) {
            return;
        }

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
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) {
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        continue;
                    }

                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        ['status' => 1]
                    );

                    $product = $this->findProduct($productName);
                    if (!$product) {
                        continue;
                    }

                    if ($totalPrice <= 0) {
                        $totalPrice = $quantity * $unitPrice;
                    }

                    $sale = InformalSale::where('year', $year)
                        ->where('number', $invoiceNumber)
                        ->first();

                    if (!$sale) {
                        $status = 'pending';
                        if ($paymentStatus == '1' ||
                            strtolower($paymentStatus) == 'بله' ||
                            strtolower($paymentStatus) == 'paid') {
                            $status = 'paid';
                        }

                        $sale = InformalSale::create([
                            'year' => $year,
                            'number' => $invoiceNumber,
                            'date' => $gregorianDate,
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'total_price' => 0,
                            'status' => $status,
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

                } catch (\Exception $e) {
                    // ادامه
                }
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

        if (!$sheet) {
            return;
        }

        $rows = $sheet->toArray();
        array_shift($rows);

        if (empty($rows)) {
            return;
        }

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
                        empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) {
                        continue;
                    }

                    $dateStr = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    try {
                        $jalaliDate = Jalalian::fromFormat('Y/m/d', $dateStr);
                        $gregorianDate = $jalaliDate->toCarbon();
                    } catch (\Exception $e) {
                        continue;
                    }

                    $product = $this->findProduct($productName);
                    if (!$product) {
                        continue;
                    }

                    if ($priceAfterDiscount <= 0) {
                        $priceAfterDiscount = $quantity * $unitPrice;
                    }

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

                } catch (\Exception $e) {
                    // ادامه
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
                    if (isset($invoiceStatus[$invNum])) {
                        $sale->status = $invoiceStatus[$invNum];
                    }
                    $sale->save();

                    foreach ($sale->products as $product) {
                        $calc = $this->calculateBoxAndLayer($product->product_id, $product->quantity);
                        $this->decreaseStockDB($product->product_id, $product->quantity, $calc['box'], $calc['layer'], $calc['pallet']);
                    }
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

                    $productWeight = $product ? $product->weight : null;

                    $production = Production::create([
                        'date' => $dateStr,
                        'operator_id' => $operator->id,
                        'press_id' => $press?->id,
                        'product_id' => $product->id,
                        'product_weight' => $productWeight,
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
                } catch (\Exception $e) {
                    // ادامه
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ✅ اصلاح‌شده: کسر کارتن و لایه با متد جدید
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

                        if ($packaged) {
                            $product->subtractPackaging($outputQty);
                        }
                    }
                } catch (\Exception $e) {
                    // ادامه
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ✅ اصلاح‌شده: کسر کارتن و لایه با متد جدید
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
                        'year' => $yearNum,
                        'month' => $monthNum,
                        'day' => $dayNum,
                        'kiln_type' => $kilnType,
                        'date' => $jalaliDate->toCarbon(),
                        'items' => []
                    ];
                }

                $groups[$groupKey]['items'][] = [
                    'product_id' => $product->id,
                    'output_quantity' => $mainQty,
                    'is_packaged' => $packaged,
                    'firing_subtype' => $firingSubtype,
                ];

            } catch (\Exception $e) {
                // ادامه
            }
        }

        usort($groups, function ($a, $b) {
            return strcmp($a['year'] . '-' . $a['month'] . '-' . $a['day'],
                          $b['year'] . '-' . $b['month'] . '-' . $b['day']);
        });

        DB::beginTransaction();
        try {
            $monthlyCounters = [];

            foreach ($groups as $group) {
                $key = $group['year'] . '-' . $group['month'] . '-' . $group['kiln_type'];

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

                    if ($item['is_packaged']) {
                        $product = Product::find($item['product_id']);
                        if ($product) {
                            $product->subtractPackaging($item['output_quantity']);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ============================================================
    //  متدهای کمکی
    // ============================================================

    private function calculateBoxAndLayer($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['box' => 0, 'layer' => 0, 'pallet' => 0];
        }

        $box = 0;
        $layer = 0;
        $pallet = 0;

        if ($product->per_box && $product->per_box > 0) {
            $box = intval($quantity / $product->per_box);
        }
        if ($product->layers_per_box && $product->layers_per_box > 0 && $product->per_box > 0) {
            $perLayer = $product->per_box * $product->layers_per_box;
            $layer = intval($quantity / $perLayer);
        }
        if ($product->per_pallet && $product->per_pallet > 0) {
            $pallet = intval($quantity / $product->per_pallet);
        }

        return ['box' => $box, 'layer' => $layer, 'pallet' => $pallet];
    }

    private function decreaseStockDB($productId, $quantity, $box, $layer, $pallet)
    {
        $existing = DB::table('inventories')->where('product_id', $productId)->first();

        if ($existing) {
            DB::table('inventories')
                ->where('product_id', $productId)
                ->update([
                    'quantity' => max(0, $existing->quantity - $quantity),
                    'box' => max(0, $existing->box - $box),
                    'layer' => max(0, $existing->layer - $layer),
                    'pallet' => max(0, $existing->pallet - $pallet),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('inventories')->insert([
                'product_id' => $productId,
                'quantity' => 0,
                'box' => 0,
                'layer' => 0,
                'pallet' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private $operatorsCache = [];
    private $productsCache = [];
    private $pressesCache = [];
    private $customersCache = [];

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
            if ($num >= 1 && $num <= 4) {
                return 'kiln_' . $num;
            }
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
        if (strpos($reason, 'قالب') !== false || strpos($reason, 'تعویض') !== false) {
            return 'تعویض قالب';
        }
        if (strpos($reason, 'خرابی') !== false || strpos($reason, 'ماشین') !== false) {
            return 'خرابی ماشین';
        }
        return 'سایر';
    }
}