<?php

use App\Http\Controllers\Partner\BusinessController;
use App\Http\Controllers\Partner\DashboardController;
use App\Http\Controllers\Partner\ListingController;
use Illuminate\Support\Facades\Route;

/*
| Business partner area, served under /partner with route names prefixed "partner.".
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('business', [BusinessController::class, 'edit'])->name('business.edit');
Route::put('business', [BusinessController::class, 'update'])->name('business.update');

Route::resource('listings', ListingController::class)->except('show');
Route::post('listings/{listing}/submit', [ListingController::class, 'submit'])->name('listings.submit');
