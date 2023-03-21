<?php

use App\Http\Controllers\api\PayPalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Paypal Payment
Route::prefix('paypal')->group(function () {
    Route::post('payment' , [PayPalController::class, 'payment'])->name('payment');
    Route::any('paymentSuccess' , [PayPalController::class, 'paymentSuccess'])->name('paymentSuccess');
    Route::any('paymentFail' , [PayPalController::class, 'paymentFail'])->name('paymentFail');
});
