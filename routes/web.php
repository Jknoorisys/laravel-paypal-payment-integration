<?php

use App\Http\Controllers\PayPalController;
use App\Models\Products;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        $data['products'] = Products::all();
        return view('dashboard',$data);
    })->name('dashboard');

    Route::any('create-transaction', [PayPalController::class, 'createTransaction'])->name('createTransaction');
    Route::any('process-transaction', [PayPalController::class, 'processTransaction'])->name('process-transaction');
    Route::any('success-transaction', [PayPalController::class, 'successTransaction'])->name('success-transaction');
    Route::any('cancel-transaction', [PayPalController::class, 'cancelTransaction'])->name('cancel-transaction');
});

