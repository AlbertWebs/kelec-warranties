<?php

use App\Http\Controllers\Customer\Auth\AuthenticatedCustomerSessionController;
use App\Http\Controllers\Customer\Auth\RegisteredCustomerController;
use App\Http\Controllers\Customer\ClaimController;
use App\Http\Controllers\Customer\WarrantyController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:customer')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/register', [RegisteredCustomerController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredCustomerController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedCustomerSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedCustomerSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth:customer')->prefix('customer')->name('customer.')->group(function () {
    Route::post('/logout', [AuthenticatedCustomerSessionController::class, 'destroy'])->name('logout');

    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');

    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/create', [ClaimController::class, 'create'])->name('claims.create');
    Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
});
