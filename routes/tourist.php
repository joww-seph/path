<?php

use App\Http\Controllers\Tourist\DashboardController;
use App\Http\Controllers\Tourist\EmergencyContactController;
use App\Http\Controllers\Tourist\ItineraryItemController;
use App\Http\Controllers\Tourist\PreferencesController;
use App\Http\Controllers\Tourist\TripController;
use App\Http\Controllers\Tourist\TripMemberController;
use App\Http\Controllers\Tourist\TripPrintController;
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
