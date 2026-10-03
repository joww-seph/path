<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HotlineController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| System administrator area, served under /admin with route names prefixed "admin.".
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

Route::get('activity', ActivityLogController::class)->name('activity.index');

Route::resource('hotlines', HotlineController::class)->only(['index', 'store', 'update', 'destroy']);
