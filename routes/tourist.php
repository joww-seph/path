<?php

use App\Http\Controllers\Tourist\BookingController;
use App\Http\Controllers\Tourist\BudgetController;
use App\Http\Controllers\Tourist\DashboardController;
use App\Http\Controllers\Tourist\EmergencyContactController;
use App\Http\Controllers\Tourist\ExpenseController;
use App\Http\Controllers\Tourist\ItineraryItemController;
use App\Http\Controllers\Tourist\PreferencesController;
use App\Http\Controllers\Tourist\ReviewController;
use App\Http\Controllers\Tourist\SosController;
use App\Http\Controllers\Tourist\TripController;
use App\Http\Controllers\Tourist\TripMemberController;
use App\Http\Controllers\Tourist\TripPrintController;
use App\Http\Controllers\Tourist\TripRecapController;
use App\Http\Controllers\Tourist\TripShareController;
use Illuminate\Support\Facades\Route;

/*
| Tourist area, served under /my with route names prefixed "tourist.".
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
Route::put('preferences', [PreferencesController::class, 'update'])->name('preferences.update');

Route::resource('emergency-contacts', EmergencyContactController::class)
    ->only(['index', 'store', 'update', 'destroy']);

Route::resource('trips', TripController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::post('trips/{trip}/arrange', [TripController::class, 'arrange'])->name('trips.arrange');
Route::get('trips/{trip}/pdf', TripPrintController::class)->name('trips.pdf');

Route::scopeBindings()->group(function () {
    Route::post('trips/{trip}/items', [ItineraryItemController::class, 'store'])->name('trips.items.store');
    Route::patch('trips/{trip}/items/{item}', [ItineraryItemController::class, 'update'])->name('trips.items.update');
    Route::delete('trips/{trip}/items/{item}', [ItineraryItemController::class, 'destroy'])->name('trips.items.destroy');
});
Route::post('trips/{trip}/reorder', [ItineraryItemController::class, 'reorder'])->name('trips.reorder');

Route::post('trips/{trip}/members', [TripMemberController::class, 'store'])->name('trips.members.store');
Route::patch('trips/{trip}/members/{member}', [TripMemberController::class, 'update'])->name('trips.members.update');
Route::delete('trips/{trip}/members/{member}', [TripMemberController::class, 'destroy'])->name('trips.members.destroy');

Route::post('trips/{trip}/share', [TripShareController::class, 'store'])->name('trips.share.store');
Route::delete('trips/{trip}/share', [TripShareController::class, 'destroy'])->name('trips.share.destroy');

Route::get('trips/{trip}/budget', BudgetController::class)->name('trips.budget');
Route::scopeBindings()->group(function () {
    Route::post('trips/{trip}/expenses', [ExpenseController::class, 'store'])->name('trips.expenses.store');
    Route::put('trips/{trip}/expenses/{expense}', [ExpenseController::class, 'update'])->name('trips.expenses.update');
    Route::delete('trips/{trip}/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('trips.expenses.destroy');
});

Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::post('bookings', [BookingController::class, 'store'])->middleware('throttle:20,1')->name('bookings.store');
Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
Route::get('bookings/{booking}/pdf', [BookingController::class, 'pdf'])->name('bookings.pdf');

Route::get('trips/{trip}/recap', TripRecapController::class)->name('trips.recap');

Route::post('listings/{listing}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');
Route::put('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::get('sos', [SosController::class, 'show'])->name('sos');
Route::post('sos', [SosController::class, 'store'])->middleware('throttle:5,10')->name('sos.store');
