<?php

use App\Http\Controllers\AdditionalController;
use App\Http\Controllers\AdditionalDefaultController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PacketController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
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
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');

    Route::resource('additionals', AdditionalController::class);
    Route::resource('additional-defaults', AdditionalDefaultController::class);
    Route::resource('products', ProductController::class);

    Route::put('/packets/{packet}/toggle-status', [PacketController::class, 'toggleStatus'])
        ->name('packets.toggle-status');

    Route::resource('packets', PacketController::class);
    Route::get('packets/product/{id}', [PacketController::class, 'product'])->name('packets.product');
    Route::get('/packets/{packet}/default-additionals', [TransaksiController::class, 'getDefaultAdditionals'])->name('packets.default-additionals');

    Route::resource('expenses', ExpenseController::class);
            
    Route::resource('transaksi', TransaksiController::class);
    Route::get('transaksi/{transaksi}/select-photos', [TransaksiController::class, 'viewSelectPhotos'])->name('transaksi.view-select-photos');
    Route::get('transaksi/{transaksi}/result-photos', [TransaksiController::class, 'viewResultPhotos'])->name('transaksi.view-result-photos');
    Route::get('/transaksi/{transaksi}/download-invoice', [TransaksiController::class, 'downloadInvoice'])
        ->name('transaksi.download-invoice');
    Route::post('/transaksi/{transaksi}/select-image', [TransaksiController::class, 'selectImage'])->name('transaksi.select-image');

    // Route::put('/transaksi/{id}/toggle-status', [TransaksiController::class, 'toggleStatus'])->name('transaksi.toggle-status');
    Route::put('/transaksi/{id}/update-status', [TransaksiController::class, 'updateStatus'])->name('transaksi.update-status');
});


Route::get('{any}', [HomeController::class, 'index']);

Route::get('index/{locale}', [HomeController::class, 'lang']);

Route::post('/formsubmit', [HomeController::class, 'FormSubmit'])->name('FormSubmit');