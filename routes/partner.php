<?php

use App\Http\Controllers\Partner\AvailabilityController;
use App\Http\Controllers\Partner\BookingController;
use App\Http\Controllers\Partner\BusinessController;
use App\Http\Controllers\Partner\CheckInController;
use App\Http\Controllers\Partner\DashboardController;
use App\Http\Controllers\Partner\ListingController;
use App\Http\Controllers\Partner\ReportController;
use Illuminate\Support\Facades\Route;

/*
| Business partner area, served under /partner with route names prefixed "partner.".
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('business', [BusinessController::class, 'edit'])->name('business.edit');
Route::put('business', [BusinessController::class, 'update'])->name('business.update');

Route::resource('listings', ListingController::class)->except('show');
Route::post('listings/{listing}/submit', [ListingController::class, 'submit'])->name('listings.submit');

Route::get('listings/{listing}/availability', [AvailabilityController::class, 'show'])->name('listings.availability');
Route::put('listings/{listing}/availability', [AvailabilityController::class, 'update'])->name('listings.availability.update');
Route::put('listings/{listing}/default-slots', [AvailabilityController::class, 'updateDefault'])->name('listings.default-slots');

Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
Route::post('bookings/{booking}/decline', [BookingController::class, 'decline'])->name('bookings.decline');
Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

Route::get('check-in', [CheckInController::class, 'scanner'])->name('check-in.scanner');
Route::get('check-in/{token}', [CheckInController::class, 'show'])->name('check-in.show');
Route::post('check-in', [CheckInController::class, 'store'])->middleware('throttle:30,1')->name('check-in.store');

Route::get('reports', ReportController::class)->name('reports');
