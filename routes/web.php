<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\TonneliFiringController;
use App\Http\Controllers\ShuttleFiringController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InformalSaleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\PressController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\PackagingController;
use App\Http\Controllers\RawMaterialPurchaseController;
use App\Http\Controllers\PackagingPurchaseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OpeningInventoryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UndoController;
use App\Http\Controllers\ProductLogController;
use App\Http\Controllers\CostPriceController;
use App\Http\Controllers\TestController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/add-product', [DashboardController::class, 'addProduct'])->name('dashboard.add-product');
    Route::post('/dashboard/remove-product', [DashboardController::class, 'removeProduct'])->name('dashboard.remove-product');

    // تولید
    Route::resource('productions', ProductionController::class);
    Route::get('/productions/by-date', [ProductionController::class, 'showByDate'])->name('productions.by-date');
    Route::delete('/productions/group/{year}/{month}/{day}', [ProductionController::class, 'destroyGroup'])->name('productions.destroy-group');

    // کوره تونلی
    Route::resource('tonneli', TonneliFiringController::class);

    // کوره شاتل
    Route::get('/shuttle', [ShuttleFiringController::class, 'index'])->name('shuttle.index');
    Route::get('/shuttle/create', [ShuttleFiringController::class, 'create'])->name('shuttle.create');
    Route::post('/shuttle', [ShuttleFiringController::class, 'store'])->name('shuttle.store');
    Route::get('/shuttle/{year}/{month}/{day}/{kiln_type}/{firingNumber}', [ShuttleFiringController::class, 'show'])->name('shuttle.show');
    Route::get('/shuttle/{year}/{month}/{day}/{kiln_type}/{firingNumber}/edit', [ShuttleFiringController::class, 'edit'])->name('shuttle.edit');
    Route::put('/shuttle/{year}/{month}/{day}/{kiln_type}/{firingNumber}', [ShuttleFiringController::class, 'update'])->name('shuttle.update');
    Route::delete('/shuttle/{year}/{month}/{day}/{kiln_type}/{firingNumber}', [ShuttleFiringController::class, 'destroy'])->name('shuttle.destroy');

    // فروش رسمی
    Route::resource('sales', SaleController::class);
    Route::post('/sales/{sale}/mark-paid', [SaleController::class, 'markAsPaid'])->name('sales.mark-paid');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

    // فروش غیررسمی
    Route::resource('informal-sales', InformalSaleController::class);
    Route::post('/informal-sales/{informalSale}/mark-paid', [InformalSaleController::class, 'markAsPaid'])->name('informal-sales.mark-paid');
    Route::post('/informal-sales/{informalSale}/cancel', [InformalSaleController::class, 'cancel'])->name('informal-sales.cancel');

    // مشتریان
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // محصولات
    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::post('/products/{product}/toggle-production', [ProductController::class, 'toggleInProduction'])->name('products.toggle-production');

    // اپراتورها
    Route::resource('operators', OperatorController::class);
    Route::post('/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status');

    // پرس‌ها
    Route::resource('presses', PressController::class);
    Route::post('/presses/{press}/toggle-status', [PressController::class, 'toggleStatus'])->name('presses.toggle-status');

    // مواد اولیه
    Route::resource('raw-materials', RawMaterialController::class);

    // فرمول‌ها
    Route::resource('formulas', FormulaController::class);

    // کارتن و لایه
    Route::resource('packagings', PackagingController::class);

    // خرید مواد اولیه
    Route::resource('raw-material-purchases', RawMaterialPurchaseController::class);

    // خرید کارتن و لایه
    Route::resource('packaging-purchases', PackagingPurchaseController::class);

    // موجودی
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/raw-materials', [InventoryController::class, 'rawMaterialsStock'])->name('inventory.raw-materials');
    Route::get('/inventory/raw', [InventoryController::class, 'raw'])->name('inventory.raw');
    Route::get('/inventory/mum', [InventoryController::class, 'mum'])->name('inventory.mum');
    Route::get('/inventory/glaze1300', [InventoryController::class, 'glaze1300'])->name('inventory.glaze1300');
    Route::get('/inventory/packaging-stock', [InventoryController::class, 'packagingStock'])->name('inventory.packaging-stock');
    Route::get('/inventory/warehouse', [InventoryController::class, 'warehouse'])->name('inventory.warehouse');
    Route::get('/inventory/shoulder', [InventoryController::class, 'shoulder'])->name('inventory.shoulder');
    Route::get('/inventory/all-stocks', [InventoryController::class, 'allStocks'])->name('inventory.all-stocks');

    // موجودی اول دوره
    Route::resource('opening-inventories', OpeningInventoryController::class);

    // واردات
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/from-path', [ImportController::class, 'importFromPath'])->name('import.from-path');
    Route::post('/import/productions', [ImportController::class, 'importProductions'])->name('import.productions');
    Route::post('/import/tonneli', [ImportController::class, 'importTonneli'])->name('import.tonneli');
    Route::post('/import/shuttle', [ImportController::class, 'importShuttle'])->name('import.shuttle');
    Route::post('/import/informal-sales', [ImportController::class, 'importInformalSales'])->name('import.informal-sales');
    Route::post('/import/formal-sales', [ImportController::class, 'importFormalSales'])->name('import.formal-sales');
    Route::post('/import/shoulder', [ImportController::class, 'importShoulder'])->name('import.shoulder');

    // گزارشات
    Route::get('/reports/production', [ReportController::class, 'production'])->name('reports.production');
    Route::get('/reports/production/export', [ReportController::class, 'exportProductionCSV'])->name('reports.production.export');
    Route::get('/reports/firing', [ReportController::class, 'firing'])->name('reports.firing');
    Route::get('/reports/annual', [ReportController::class, 'annual'])->name('reports.annual');

    // قیمت تمام شده
    Route::get('/cost-price', [CostPriceController::class, 'index'])->name('cost-price.index');

    // تاریخچه تغییرات محصولات
    Route::get('/product-logs', [ProductLogController::class, 'index'])->name('product_logs.index');

    // Undo (بازگرداندن)
    Route::get('/undo/restore', [UndoController::class, 'restore'])->name('undo.restore');
    Route::get('/undo/discard', [UndoController::class, 'discard'])->name('undo.discard');

    // تست (در صورت نیاز)
    Route::get('/test', [TestController::class, 'index'])->name('test.index');
    Route::post('/test', [TestController::class, 'store'])->name('test.store');

    // پروفایل کاربر
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';