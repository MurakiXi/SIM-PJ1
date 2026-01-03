<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StripeWebhookController;


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

// PG01,05
Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/item/{item}', [ItemController::class, 'show'])->name('items.show');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

//PG06,07,08,09,10
Route::middleware('auth')->group(function () {

    // PG06
    Route::get('/purchase/{item}', [PurchaseController::class, 'purchase'])->name('purchase.show');
    Route::post('/purchase/{item}', [PurchaseController::class, 'checkout'])->name('purchase.checkout');

    // PG07
    Route::get('/purchase/address/{item}', [PurchaseController::class, 'editAddress'])->name('purchase.address.edit');
    Route::patch('/purchase/address/{item}', [PurchaseController::class, 'updateAddress'])->name('purchase.address.update');

    // PG08
    Route::get('/sell', [ItemController::class, 'create'])->name('sell.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('sell.store');

    // PG09
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    // PG10
    Route::get('/mypage/profile', [MypageController::class, 'edit'])->name('profile.edit');
    Route::patch('/mypage/profile', [MypageController::class, 'update'])->name('profile.update');

    Route::get('/purchase/{item}/success', [PurchaseController::class, 'success'])->name('purchase.success');
    Route::get('/purchase/{item}/cancel',  [PurchaseController::class, 'cancel'])->name('purchase.cancel');

    Route::post('/item/{item}/like', [ItemController::class, 'toggleLike'])->name('items.like');
    Route::post('/item/{item}/comment', [ItemController::class, 'storeComment'])->name('items.comment');
});
