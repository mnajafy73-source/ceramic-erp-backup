<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PressController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductLogController;
use App\Http\Controllers\TonneliFiringController;
use App\Http\Controllers\ShuttleFiringController;
use App\Http\Controllers\UndoController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SaleController;

Route::get('/', function () { return view('welcome'); });
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::resource('operators', OperatorController::class)->middleware('auth');
Route::resource('presses', PressController::class)->middleware('auth');
Route::resource('products', ProductController::class)->middleware('auth');
Route::resource('productions', ProductionController::class)->middleware('auth');

// ==================== کوره تونلی ====================
Route::get('/tonneli', [TonneliFiringController::class, 'index'])->name('tonneli.index')->middleware('auth');
Route::get('/tonneli/create', [TonneliFiringController::class, 'create'])->name('tonneli.create')->middleware('auth');
Route::post('/tonneli', [TonneliFiringController::class, 'store'])->name('tonneli.store')->middleware('auth');
Route::delete('/tonneli/{tonneli}', [TonneliFiringController::class, 'destroy'])->name('tonneli.destroy')->middleware('auth');

Route::get('/tonneli/{tonneli}', function (App\Models\TonneliFiring $tonneli) {
    $tonneli->load('product');
    $date = $tonneli->jalali_date ?? 'ندارد';
    $product = $tonneli->product->name ?? 'ندارد';
    $input = $tonneli->input_quantity;
    $output = $tonneli->output_quantity;
    $packaged = $tonneli->is_packaged ? 'بله' : 'خیر';

    return '<!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head><meta charset="UTF-8"><title>جزئیات پخت تونلی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet"></head>
    <body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>جزئیات پخت تونلی</h4>
                <p><strong>تاریخ:</strong> ' . $date . '</p>
                <p><strong>محصول:</strong> ' . $product . '</p>
                <p><strong>ورودی:</strong> ' . $input . '</p>
                <p><strong>خروجی:</strong> ' . $output . '</p>
                <p><strong>بسته‌بندی:</strong> ' . $packaged . '</p>
                <a href="/tonneli" class="btn btn-secondary">بازگشت</a>
                <a href="/tonneli/' . $tonneli->id . '/edit" class="btn btn-warning ms-2">ویرایش</a>
            </div>
        </div>
    </div>
    </body></html>';
})->name('tonneli.show')->middleware('auth');

Route::get('/tonneli/{tonneli}/edit', function (App\Models\TonneliFiring $tonneli) {
    $tonneli->load('product');
    $products = App\Models\Product::where('status', true)->whereIn('kiln_type', ['tonneli', 'both'])->get();
    $date = $tonneli->jalali_date;
    $input = $tonneli->input_quantity;
    $output = $tonneli->output_quantity;
    $packagedChecked = $tonneli->is_packaged ? 'checked' : '';

    $productOptions = '';
    foreach ($products as $p) {
        $selected = $p->id == $tonneli->product_id ? 'selected' : '';
        $productOptions .= '<option value="' . $p->id . '" ' . $selected . '>' . $p->name . '</option>';
    }

    return '<!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head><meta charset="UTF-8"><title>ویرایش پخت تونلی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    </head>
    <body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h4>ویرایش پخت تونلی</h4>
                <form action="/tonneli/' . $tonneli->id . '" method="POST">
                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="mb-3">
                        <label>تاریخ <span class="text-danger">*</span></label>
                        <input type="text" name="date" id="date" class="form-control" value="' . $date . '" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label>محصول <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-control" required>
                            <option value="">انتخاب کنید...</option>
                            ' . $productOptions . '
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>ورودی</label>
                        <input type="number" name="input_quantity" class="form-control" value="' . $input . '" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label>خروجی</label>
                        <input type="number" name="output_quantity" class="form-control" value="' . $output . '" step="0.01">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_packaged" id="is_packaged" value="1" ' . $packagedChecked . '>
                            <label class="form-check-label" for="is_packaged">بسته‌بندی شده</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">بروزرسانی</button>
                    <a href="/tonneli" class="btn btn-secondary ms-2">انصراف</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        $(function() {
            $("#date").persianDatepicker({ format: "YYYY/MM/DD", autoClose: true, initialValue: false, observer: true, calendar: { persian: { locale: "fa" } } });
        });
    </script>
    </body></html>';
})->name('tonneli.edit')->middleware('auth');

Route::put('/tonneli/{tonneli}', function (App\Models\TonneliFiring $tonneli, Request $request) {
    $data = $request->validate([
        'date' => 'required|string',
        'product_id' => 'required|exists:products,id',
        'input_quantity' => 'nullable|numeric|min:0',
        'output_quantity' => 'nullable|numeric|min:0',
        'is_packaged' => 'boolean',
    ]);

    try {
        $data['date'] = Jalalian::fromFormat('Y/m/d', $data['date'])->toCarbon()->format('Y-m-d');
    } catch (\Exception $e) {
        return back()->withErrors(['date' => 'فرمت تاریخ شمسی نادرست است.'])->withInput();
    }

    $data['is_packaged'] = $request->has('is_packaged');
    $tonneli->update($data);

    return redirect()->to('/tonneli')->with('success', 'ویرایش شد.');
})->name('tonneli.update')->middleware('auth');

Route::patch('/tonneli/{tonneli}/toggle-packaged', function (App\Models\TonneliFiring $tonneli) {
    $tonneli->is_packaged = !$tonneli->is_packaged;
    $tonneli->save();

    return response()->json([
        'success' => true,
        'is_packaged' => $tonneli->is_packaged,
    ]);
})->name('tonneli.toggle-packaged')->middleware('auth');

// ==================== کوره شاتل ====================
Route::get('/shuttle', [ShuttleFiringController::class, 'index'])->name('shuttle.index')->middleware('auth');
Route::get('/shuttle/create', [ShuttleFiringController::class, 'create'])->name('shuttle.create')->middleware('auth');
Route::post('/shuttle', [ShuttleFiringController::class, 'store'])->name('shuttle.store')->middleware('auth');

Route::get('/shuttle/batch/{firingNumber}', [ShuttleFiringController::class, 'show'])->name('shuttle.show')->middleware('auth');
Route::get('/shuttle/batch/{firingNumber}/edit', [ShuttleFiringController::class, 'edit'])->name('shuttle.edit')->middleware('auth');
Route::put('/shuttle/batch/{firingNumber}', [ShuttleFiringController::class, 'update'])->name('shuttle.update')->middleware('auth');

Route::delete('/shuttle/{shuttle}', [ShuttleFiringController::class, 'destroy'])->name('shuttle.destroy')->middleware('auth');
Route::post('/shuttle/batch/delete', [ShuttleFiringController::class, 'destroyBatch'])->name('shuttle.destroy-batch')->middleware('auth');

Route::patch('/shuttle/{shuttle}/toggle-packaged', function (App\Models\ShuttleFiring $shuttle) {
    $shuttle->is_packaged = !$shuttle->is_packaged;
    $shuttle->save();
    return response()->json(['success' => true, 'is_packaged' => $shuttle->is_packaged]);
})->name('shuttle.toggle-packaged')->middleware('auth');

// ==================== حواله ====================
Route::resource('invoices', InvoiceController::class)->middleware('auth');
Route::post('/invoices/{invoice}/close', [InvoiceController::class, 'close'])->name('invoices.close')->middleware('auth');

// ==================== فروش رسمی ====================
Route::resource('sales', SaleController::class)->middleware('auth');
Route::post('/sales/{sale}/mark-paid', [SaleController::class, 'markAsPaid'])->name('sales.paid')->middleware('auth');
Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel')->middleware('auth');
Route::get('/sales/invoice-products/{invoice}', [SaleController::class, 'getInvoiceProducts'])->name('sales.invoice-products')->middleware('auth');

// ==================== Undo ====================
Route::get('/undo/restore', [UndoController::class, 'restore'])->name('undo.restore')->middleware('auth');
Route::get('/undo/discard', [UndoController::class, 'discard'])->name('undo.discard')->middleware('auth');

// ==================== Toggle های دیگر ====================
Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status')->middleware('auth');
Route::patch('/products/{product}/toggle-in-production', [ProductController::class, 'toggleInProduction'])->name('products.toggle-in-production')->middleware('auth');
Route::patch('/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status')->middleware('auth');
Route::patch('/presses/{press}/toggle-status', [PressController::class, 'toggleStatus'])->name('presses.toggle-status')->middleware('auth');

Route::resource('product_logs', ProductLogController::class)->only(['index'])->middleware('auth');

require __DIR__.'/auth.php';