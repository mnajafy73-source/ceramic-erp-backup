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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    //  واردات خودکار از مسیر (دکمه)
    // ============================================================
    public function importFromPath()
    {
        $filePath = env('EXCEL_FILE_PATH');

        if (empty($filePath) || !file_exists($filePath)) {
            return redirect()->route('import.index')
                ->withErrors(['file' => 'مسیر فایل اکسل در فایل .env تنظیم نشده یا فایل وجود ندارد.']);
        }

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

            $this->importProductionsFromSpreadsheet($spreadsheet);
            $this->importTonneliFromSpreadsheet($spreadsheet);
            $this->importShuttleFromSpreadsheet($spreadsheet);

            return redirect()->route('import.index')
                ->with('success', '✅ تمام برگه‌ها با موفقیت از مسیر وارد شدند.');
        } catch (\Exception $e) {
            return redirect()->route('import.index')
                ->withErrors(['file' => 'خطا در خواندن فایل: ' . $e->getMessage()]);
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

                    $production = Production::create([
                        'date' => $dateStr,
                        'operator_id' => $operator->id,
                        'press_id' => $press?->id,
                        'product_id' => $product->id,
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

        $message = "✅ {$count} رکورد تولید با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه کوره تونلی (آپلود دستی)
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

                        if ($packaged) {
                            $this->subtractPackagingForTonneli($product, $outputQty);
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

        $message = "✅ {$count} رکورد کوره تونلی با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه کوره شاتل (آپلود دستی)
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
                }

                $count += count($group['items']);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'خطا در حین ذخیره‌سازی: ' . $e->getMessage()]);
        }

        $message = "✅ {$count} رکورد کوره شاتل در " . count($groups) . " پخت با موفقیت وارد شد.";
        if (!empty($errors)) {
            $message .= " ⚠️ خطاها: " . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $message .= " و " . (count($errors) - 5) . " خطای دیگر.";
        }

        return redirect()->route('import.index')->with('success', $message);
    }

    // ============================================================
    //  برگه فروش غیررسمی (نسخه نهایی بدون نقص)
    //  ستون‌ها: سال | ماه | روز | شماره فاکتور | نام مشتری | محصول | تعداد | بهای واحد | قیمت کل | وضعیت پرداخت
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

        // پیدا کردن برگه
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
        array_shift($rows); // حذف سطر عنوان

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

                    if ($year < 1400 || $month < 1 || $month > 12 || $day < 1 || $day > 31 || empty($invoiceNumber) || empty($customerName) || empty($productName) || $quantity <= 0) {
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

                    // پیدا کردن یا ایجاد مشتری
                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        ['status' => 1]
                    );

                    // پیدا کردن یا ایجاد محصول (با alias)
                    $product = $this->findProductForSale($productName);
                    if (!$product) {
                        $errors[] = "ردیف " . ($rowIndex + 2) . ": محصول {$productName} پیدا نشد.";
                        continue;
                    }

                    // پیدا کردن فاکتور با شماره و سال
                    $sale = InformalSale::where('year', $year)
                        ->where('number', $invoiceNumber)
                        ->first();

                    if (!$sale) {
                        $sale = InformalSale::create([
                            'year' => $year,
                            'number' => $invoiceNumber,
                            'date' => $gregorianDate,
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'total_price' => 0,
                            'status' => ($paymentStatus == '1' || strtolower($paymentStatus) == 'بله' || strtolower($paymentStatus) == 'paid') ? 'paid' : 'unpaid',
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

                    if ($paymentStatus == '1' || strtolower($paymentStatus) == 'بله' || strtolower($paymentStatus) == 'paid') {
                        $sale->status = 'paid';
                        $sale->save();
                    }

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
    //  متدهای کمکی
    // ============================================================

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

    private function findProductForSale($name)
    {
        $cleanName = trim($name);

        if (isset($this->productsCache[$cleanName])) {
            return $this->productsCache[$cleanName];
        }

        $alias = ProductAlias::where('alias', $cleanName)->first();
        if ($alias) {
            $product = $alias->product;
            $this->productsCache[$cleanName] = $product;
            return $product;
        }

        $product = Product::where('name', $cleanName)->first();
        if ($product) {
            $this->productsCache[$cleanName] = $product;
            return $product;
        }

        $product = Product::create([
            'code' => 'SALE-' . time() . '-' . rand(100, 999),
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

    private function getOrCreateCustomer($name)
    {
        $cleanName = trim($name);
        if (empty($cleanName)) {
            $cleanName = 'مشتری ناشناس';
        }

        if (isset($this->customersCache[$cleanName])) {
            return $this->customersCache[$cleanName];
        }

        $customer = Customer::firstOrCreate(
            ['name' => $cleanName],
            ['status' => 1]
        );

        $this->customersCache[$cleanName] = $customer;
        return $customer;
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

    private function subtractPackagingForTonneli($product, $quantity)
    {
        if ($quantity <= 0) return;

        if ($product->carton_packaging_id && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $carton = Packaging::find($product->carton_packaging_id);
            if ($carton) {
                $carton->stock -= $cartonCount;
                $carton->save();
            }
        }

        if ($product->layer_packaging_id && $product->layers_per_box > 0 && $product->per_box > 0) {
            $cartonCount = ceil($quantity / $product->per_box);
            $layerCount = $cartonCount * $product->layers_per_box;
            $layer = Packaging::find($product->layer_packaging_id);
            if ($layer) {
                $layer->stock -= $layerCount;
                $layer->save();
            }
        }
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

    // ============================================================
    //  متدهای واردات از مسیر (برای importFromPath)
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
                            $this->subtractPackagingForTonneli($product, $outputQty);
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
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}