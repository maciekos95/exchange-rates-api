<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CurrencyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(UserController::class)->group(function () {
    Route::post('/login', [UserController::class, 'login'])->name('login');
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/refresh', [UserController::class, 'refresh']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::post('/users/create', [UserController::class, 'create']);
    Route::post('/users/edit/{id}', [UserController::class, 'edit']);
    Route::delete('/users/delete/{id}', [UserController::class, 'delete']);
});

Route::controller(CurrencyController::class)->group(function () {
    Route::post('/currencies/add', [CurrencyController::class, 'add']);
    Route::post('/currencies/update/{code}/{date}', [CurrencyController::class, 'update']);
    Route::delete('/currencies/delete/{code}/{date}', [CurrencyController::class, 'delete']);
    Route::get('/currencies/{date}', [CurrencyController::class, 'list']);
    Route::get('/currencies/{code}/{date}', [CurrencyController::class, 'get']);
});
