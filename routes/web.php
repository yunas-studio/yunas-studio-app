<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PacketController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\AdditionalController;
use App\Http\Controllers\AdditionalDefaultController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'root']);

    // User Management
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    // Products, Packets, and Additionals
    Route::resource('additionals', AdditionalController::class);
    Route::resource('additional-defaults', AdditionalDefaultController::class);
    Route::resource('products', ProductController::class);
    Route::resource('packets', PacketController::class);
    Route::put('/packets/{packet}/toggle-status', [PacketController::class, 'toggleStatus'])->name('packets.toggle-status');
    Route::get('packets/product/{id}', [PacketController::class, 'product'])->name('packets.product');
    Route::get('/packets/{packet}/default-additionals', [TransaksiController::class, 'getDefaultAdditionals'])->name('packets.default-additionals');
    
    // Print Sizes
    Route::post('/packets/{packet}/print-options', [PacketController::class, 'addPrintOption'])->name('packets.addPrintOption');
    Route::delete('/packets/{packet}/print-options/{print_size}', [PacketController::class, 'removePrintOption'])->name('packets.removePrintOption');

    // Expenses
    Route::resource('expenses', ExpenseController::class);
    
    // Transactions Core
    Route::resource('transaksi', TransaksiController::class);
    Route::put('/transaksi/{id}/update-status', [TransaksiController::class, 'updateStatus'])->name('transaksi.update-status');
    Route::put('/transaksi/{transaksi}/complete-editing', [TransaksiController::class, 'completeEditing'])->name('transaksi.completeEditing');

    // Transaction Photo & Invoice Workflow Routes
    Route::prefix('transaksi/{transaksi}')->name('transaksi.')->group(function () {
        Route::get('/select-for-edit', [TransaksiController::class, 'viewSelectForEdit'])->name('view-select-for-edit');
        Route::post('/handle-select-for-edit', [TransaksiController::class, 'handleSelectForEdit'])->name('handle-select-for-edit');

        Route::get('/select-for-print', [TransaksiController::class, 'viewSelectForPrint'])->name('view-select-for-print');
        Route::post('/handle-select-for-print', [TransaksiController::class, 'handleSelectForPrint'])->name('handle-select-for-print');

        Route::get('/result-photos', [TransaksiController::class, 'viewResultPhotos'])->name('view-result-photos');

        Route::get('/download-invoice', [TransaksiController::class, 'downloadInvoice'])->name('download-invoice');

        Route::get('/view-selections', [TransaksiController::class, 'viewSelectionsForAdmin'])->name('view-selections');
        Route::get('/print-invoice', [TransaksiController::class, 'printInvoice'])->name('print-invoice');
    });
});

// Language and Fallback Routes
Route::get('index/{locale}', [HomeController::class, 'lang']);
Route::post('/formsubmit', [HomeController::class, 'FormSubmit'])->name('FormSubmit');
Route::get('{any}', [HomeController::class, 'index'])->where('any', '.*');