<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PartnerRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('partner/register', [PartnerRegistrationController::class, 'create'])->name('partner.register');
    Route::post('partner/register', [PartnerRegistrationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('partner.register.store');

    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('my')->name('tourist.')->middleware('role:tourist')->group(base_path('routes/tourist.php'));
    Route::prefix('partner')->name('partner.')->middleware('role:partner')->group(base_path('routes/partner.php'));
    Route::prefix('office')->name('office.')->middleware('role:tourism_officer,admin')->group(base_path('routes/office.php'));
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(base_path('routes/admin.php'));
});

require __DIR__.'/settings.php';
