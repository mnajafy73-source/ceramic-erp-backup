<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerPayment::with('customer')
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        if ($customerName = $request->input('customer_name')) {
            $query->where('customer_name', 'like', "%{$customerName}%");
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }
        if ($month = $request->input('month')) {
            $query->where('month', $month);
        }

        if ($dateFrom = $request->input('date_from')) {
            try {
                $from = Jalalian::fromFormat('Y/m/d', $dateFrom)->toCarbon()->toDateString();
                $query->where('payment_date', '>=', $from);
            } catch (\Exception $e) {}
        }
        if ($dateTo = $request->input('date_to')) {
            try {
                $to = Jalalian::fromFormat('Y/m/d', $dateTo)->toCarbon()->toDateString();
                $query->where('payment_date', '<=', $to);
            } catch (\Exception $e) {}
        }

        $payments = $query->paginate(30)->appends($request->all());

        $totalAmount = CustomerPayment::sum('amount');
        $paymentsCount = CustomerPayment::count();
        $customersCount = CustomerPayment::distinct('customer_name')->count('customer_name');

        $customers = Customer::orderBy('name')->get();
        $currentYear = Jalalian::now()->getYear();
        $currentMonth = Jalalian::now()->getMonth();

        return view('accounting.index', compact(
            'payments', 'totalAmount', 'paymentsCount', 'customersCount',
            'customers', 'currentYear', 'currentMonth'
        ));
    }

    public function importFromExcel(Request $request)
    {
        $filePath = env('EXCEL_FILE_PATH');

        if (empty($filePath) || !file_exists($filePath)) {
            return redirect()->route('accounting.index')
                ->with('error', 'مسیر فایل اکسل در .env تنظیم نشده یا فایل وجود ندارد.');
        }

        try {
            set_time_limit(0);

            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            $sheetNames = ['حسابداری', 'پرداخت‌ها', 'پرداختی‌ها', 'پرداخت'];
            $sheet = null;
            foreach ($sheetNames as $name) {
                $sheet = $spreadsheet->getSheetByName($name);
                if ($sheet) break;
            }

            if (!$sheet) {
                return redirect()->route('accounting.index')
                    ->with('error', 'شیت «حسابداری» در فایل اکسل پیدا نشد.');
            }

            $rows = $sheet->toArray();
            array_shift($rows);

            // ✅ فقط ایمپورتی‌ها رو پاک کن، دستی‌ها بمونن
            DB::statement('DELETE FROM customer_payments WHERE is_imported = 1');

            $importedCount = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($rows as $rowIndex => $row) {
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
                            'is_imported'    => true, // ✅ ایمپورتی
                        ]);

                        $importedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "خطا در ردیف " . ($rowIndex + 2) . ": " . $e->getMessage();
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $message = "✅ {$importedCount} پرداخت از اکسل ایمپورت شد.";
            if (!empty($errors)) {
                $message .= " ⚠️ " . count($errors) . " خطا رخ داد.";
            }

            return redirect()->route('accounting.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('accounting.index')
                ->with('error', 'خطا در ایمپورت: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'nullable|exists:customers,id',
            'customer_name'  => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:1',
            'date'           => 'required|string',
            'payment_method' => 'nullable|string|max:50',
            'description'    => 'nullable|string|max:500',
        ]);

        $customerId = $request->input('customer_id');
        $customerName = trim((string) $request->input('customer_name', ''));

        if (empty($customerId) && empty($customerName)) {
            return back()
                ->withErrors(['customer_name' => 'لطفاً مشتری رو از لیست انتخاب کن یا نام مشتری جدید رو بنویس.'])
                ->withInput();
        }

        try {
            $jalaliDate = Jalalian::fromFormat('Y/m/d', $validated['date']);
            $gregorianDate = $jalaliDate->toCarbon();
            $year = $jalaliDate->getYear();
            $month = $jalaliDate->getMonth();
            $day = $jalaliDate->getDay();
        } catch (\Exception $e) {
            return back()->withErrors(['date' => 'تاریخ شمسی نادرست است.'])->withInput();
        }

        if (!empty($customerId)) {
            $customer = Customer::find($customerId);
            $customerName = $customer->name;
            $customerId = $customer->id;
        } else {
            $customer = Customer::firstOrCreate(
                ['name' => $validated['customer_name']],
                ['status' => 1]
            );
            $customerName = $customer->name;
            $customerId = $customer->id;
        }

        CustomerPayment::create([
            'customer_id'    => $customerId,
            'customer_name'  => $customerName,
            'amount'         => $validated['amount'],
            'payment_date'   => $gregorianDate,
            'year'           => $year,
            'month'          => $month,
            'day'            => $day,
            'payment_method' => $validated['payment_method'] ?? null,
            'description'    => $validated['description'] ?? null,
            'is_imported'    => false, // ✅ دستی
        ]);

        return redirect()->route('accounting.index')
            ->with('success', 'پرداخت با موفقیت ثبت شد.');
    }

    public function destroy(CustomerPayment $payment)
    {
        $payment->delete();
        return redirect()->route('accounting.index')
            ->with('success', 'پرداخت حذف شد.');
    }

    public function clearAll()
    {
        // ✅ فقط ایمپورتی‌ها رو پاک کن
        $count = CustomerPayment::where('is_imported', true)->count();
        CustomerPayment::where('is_imported', true)->delete();

        return redirect()->route('accounting.index')
            ->with('success', "✅ {$count} پرداخت ایمپورتی پاک شد. (پرداخت‌های دستی حفظ شدند)");
    }

    public function debtors(Request $request)
    {
        $currentJalali = Jalalian::now();
        $year = (int) $request->input('year', $currentJalali->getYear());
        $month = (int) $request->input('month', $currentJalali->getMonth());

        $startDateStr = sprintf('%04d/%02d/01', $year, $month);
        $lastDay = Jalalian::fromFormat('Y/m/d', $startDateStr)->getMonthDays();
        $endDateStr = sprintf('%04d/%02d/%02d', $year, $month, $lastDay);

        try {
            $endDate = Jalalian::fromFormat('Y/m/d', $endDateStr)->toCarbon()->toDateString();
        } catch (\Exception $e) {
            $endDate = now()->toDateString();
        }

        $customers = Customer::orderBy('name')->get();
        $debtorsData = collect();

        foreach ($customers as $customer) {
            $formalSales = \App\Models\SaleProduct::whereHas('sale', function ($q) use ($customer, $endDate) {
                $q->where('customer_name', $customer->name)
                  ->whereDate('date', '<=', $endDate);
            })->selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;

            $informalSales = \App\Models\InformalSaleProduct::whereHas('informalSale', function ($q) use ($customer, $endDate) {
                $q->where('customer_name', $customer->name)
                  ->whereDate('date', '<=', $endDate);
            })->selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;

            $totalSales = (float) $formalSales + (float) $informalSales;

            if ($totalSales <= 0) continue;

            $totalPaid = CustomerPayment::getTotalPaidForCustomer($customer->name, $endDate);
            $remaining = $totalSales - $totalPaid;

            if (abs($remaining) < 1) continue;

            $debtorsData->push((object) [
                'customer' => $customer,
                'total_sales' => $totalSales,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
            ]);
        }

        $debtorsData = $debtorsData->sortByDesc('remaining')->values();

        $monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        return view('accounting.debtors', compact(
            'debtorsData', 'year', 'month', 'monthNames', 'currentJalali'
        ));
    }
}