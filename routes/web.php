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
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InformalSaleController;

Route::get('/', function () { return view('welcome'); });

// ==================== داشبورد ====================
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::post('/dashboard/add-product', [DashboardController::class, 'addProduct'])->name('dashboard.add-product')->middleware('auth');
Route::post('/dashboard/remove-product', [DashboardController::class, 'removeProduct'])->name('dashboard.remove-product')->middleware('auth');

// ==================== تولید ====================
Route::get('/productions', [ProductionController::class, 'index'])->name('productions.index')->middleware('auth');
Route::get('/productions/create', [ProductionController::class, 'create'])->name('productions.create')->middleware('auth');
Route::post('/productions', [ProductionController::class, 'store'])->name('productions.store')->middleware('auth');
Route::get('/productions/show-by-date', [ProductionController::class, 'showByDate'])->name('productions.show-by-date')->middleware('auth');
Route::get('/productions/{production}', [ProductionController::class, 'show'])->name('productions.show')->middleware('auth');
Route::get('/productions/{production}/edit', [ProductionController::class, 'edit'])->name('productions.edit')->middleware('auth');
Route::put('/productions/{production}', [ProductionController::class, 'update'])->name('productions.update')->middleware('auth');
Route::delete('/productions/{production}', [ProductionController::class, 'destroy'])->name('productions.destroy')->middleware('auth');

// ==================== کوره تونلی ====================
Route::get('/tonneli', [TonneliFiringController::class, 'index'])->name('tonneli.index')->middleware('auth');
Route::get('/tonneli/create', [TonneliFiringController::class, 'create'])->name('tonneli.create')->middleware('auth');
Route::post('/tonneli', [TonneliFiringController::class, 'store'])->name('tonneli.store')->middleware('auth');
Route::delete('/tonneli/{tonneli}', [TonneliFiringController::class, 'destroy'])->name('tonneli.destroy')->middleware('auth');
Route::get('/tonneli/{tonneli}', [TonneliFiringController::class, 'show'])->name('tonneli.show')->middleware('auth');
Route::get('/tonneli/{tonneli}/edit', [TonneliFiringController::class, 'edit'])->name('tonneli.edit')->middleware('auth');
Route::put('/tonneli/{tonneli}', [TonneliFiringController::class, 'update'])->name('tonneli.update')->middleware('auth');

// ==================== کوره شاتل ====================
Route::get('/shuttle', [ShuttleFiringController::class, 'index'])->name('shuttle.index')->middleware('auth');
Route::get('/shuttle/create', [ShuttleFiringController::class, 'create'])->name('shuttle.create')->middleware('auth');
Route::post('/shuttle', [ShuttleFiringController::class, 'store'])->name('shuttle.store')->middleware('auth');
Route::get('/shuttle/batch/{firingNumber}', [ShuttleFiringController::class, 'show'])->name('shuttle.show')->middleware('auth');
Route::get('/shuttle/batch/{firingNumber}/edit', [ShuttleFiringController::class, 'edit'])->name('shuttle.edit')->middleware('auth');
Route::put('/shuttle/batch/{firingNumber}', [ShuttleFiringController::class, 'update'])->name('shuttle.update')->middleware('auth');
Route::delete('/shuttle/{shuttle}', [ShuttleFiringController::class, 'destroy'])->name('shuttle.destroy')->middleware('auth');
Route::post('/shuttle/batch/delete', [ShuttleFiringController::class, 'destroyBatch'])->name('shuttle.destroy-batch')->middleware('auth');

// ==================== فروش رسمی ====================
Route::resource('sales', SaleController::class)->middleware('auth');
Route::post('/sales/{sale}/mark-paid', [SaleController::class, 'markAsPaid'])->name('sales.paid')->middleware('auth');
Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel')->middleware('auth');

// ==================== فروش غیررسمی ====================
Route::resource('informal-sales', InformalSaleController::class)->middleware('auth');
Route::post('/informal-sales/{informal_sale}/mark-paid', [InformalSaleController::class, 'markAsPaid'])->name('informal-sales.paid')->middleware('auth');
Route::post('/informal-sales/{informal_sale}/cancel', [InformalSaleController::class, 'cancel'])->name('informal-sales.cancel')->middleware('auth');

// ==================== اپراتورها ====================
Route::resource('operators', OperatorController::class)->middleware('auth');

// ==================== پرس‌ها ====================
Route::resource('presses', PressController::class)->middleware('auth');

// ==================== محصولات ====================
Route::resource('products', ProductController::class)->middleware('auth');

// ==================== لاگ محصولات ====================
Route::resource('product_logs', ProductLogController::class)->only(['index'])->middleware('auth');

// ==================== Undo ====================
Route::get('/undo/restore', [UndoController::class, 'restore'])->name('undo.restore')->middleware('auth');
Route::get('/undo/discard', [UndoController::class, 'discard'])->name('undo.discard')->middleware('auth');

// ==================== Toggle های دیگر ====================
Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status')->middleware('auth');
Route::patch('/products/{product}/toggle-in-production', [ProductController::class, 'toggleInProduction'])->name('products.toggle-in-production')->middleware('auth');
Route::patch('/operators/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operators.toggle-status')->middleware('auth');
Route::patch('/presses/{press}/toggle-status', [PressController::class, 'toggleStatus'])->name('presses.toggle-status')->middleware('auth');

require __DIR__.'/auth.php';