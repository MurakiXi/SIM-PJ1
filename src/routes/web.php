<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\PurchaseController;

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
Route::get('/', [ItemController::class, 'index'])->name('items.index');       // PG01
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show'); // PG05

// 検索
Route::get('/search', [ItemController::class, 'search'])->name('items.search');

//PG06,07,08,09,10
Route::middleware('auth')->group(function () {

    // PG06
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'purchase'])->name('purchase.show');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'checkout'])->name('purchase.checkout');

    // PG07
    Route::get('/purchase/address/{item_id}', [PurchaseController::class, 'editAddress'])->name('address.edit');
    Route::patch('/purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])->name('address.update');

    // PG08
    Route::get('/sell', [ItemController::class, 'create'])->name('sell.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('sell.store');

    // PG09
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    // PG10
    Route::get('/mypage/profile', [MypageController::class, 'edit'])->name('profile.edit');
    Route::patch('/mypage/profile', [MypageController::class, 'update'])->name('profile.update');
});

//PG03,04
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});
