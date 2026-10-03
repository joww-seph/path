<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\PartnerRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guide\EventController;
use App\Http\Controllers\Guide\ExploreController;
use App\Http\Controllers\Guide\ListingController;
use App\Http\Controllers\Guide\MapController;
use App\Http\Controllers\Guide\SharedTripController;
use App\Http\Controllers\Guide\SitemapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Listings\ListingPhotoController;
use App\Http\Controllers\Listings\ListingRateController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::get('explore', ExploreController::class)->name('explore');
Route::get('places/{listing}', [ListingController::class, 'show'])->name('listings.show');
Route::get('map', MapController::class)->name('map');
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('trips/shared/{token}', SharedTripController::class)->name('trips.shared');

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

    // Photos and rates for a listing, shared by partners and the tourism office (checked by ListingPolicy).
    Route::scopeBindings()->prefix('listings/{listing}')->name('listings.')->group(function () {
        Route::post('photos', [ListingPhotoController::class, 'store'])->name('photos.store');
        Route::patch('photos/{photo}', [ListingPhotoController::class, 'update'])->name('photos.update');
        Route::delete('photos/{photo}', [ListingPhotoController::class, 'destroy'])->name('photos.destroy');
        Route::post('rates', [ListingRateController::class, 'store'])->name('rates.store');
        Route::put('rates/{rate}', [ListingRateController::class, 'update'])->name('rates.update');
        Route::delete('rates/{rate}', [ListingRateController::class, 'destroy'])->name('rates.destroy');
    });

    Route::prefix('my')->name('tourist.')->middleware('role:tourist')->group(base_path('routes/tourist.php'));
    Route::prefix('partner')->name('partner.')->middleware('role:partner')->group(base_path('routes/partner.php'));
    Route::prefix('office')->name('office.')->middleware('role:tourism_officer,admin')->group(base_path('routes/office.php'));
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(base_path('routes/admin.php'));
});

require __DIR__.'/settings.php';
