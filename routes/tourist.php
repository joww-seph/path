<?php

use App\Http\Controllers\Tourist\DashboardController;
use App\Http\Controllers\Tourist\EmergencyContactController;
use App\Http\Controllers\Tourist\PreferencesController;
use Illuminate\Support\Facades\Route;

/*
| Tourist area, served under /my with route names prefixed "tourist.".
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('preferences', [PreferencesController::class, 'edit'])->name('preferences.edit');
Route::put('preferences', [PreferencesController::class, 'update'])->name('preferences.update');

Route::resource('emergency-contacts', EmergencyContactController::class)
    ->only(['index', 'store', 'update', 'destroy']);
