<?php

use App\Http\Controllers\Api\OfflineTripController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
| A small JSON API for what Inertia pages can't do: saving trips for offline use and syncing
| changes made without signal. It runs on the web session (loaded from web.php), so the same
| login and CSRF protection apply.
*/

Route::get('trips/{trip}/offline', OfflineTripController::class)->name('trips.offline');
Route::post('sync', SyncController::class)->middleware('throttle:30,1')->name('sync');
