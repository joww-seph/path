<?php

use App\Http\Controllers\Office\DashboardController;
use Illuminate\Support\Facades\Route;

/*
| Municipal Tourism Office area, served under /office with route names prefixed "office.".
| Administrators can also use it.
*/

Route::get('/', DashboardController::class)->name('dashboard');
