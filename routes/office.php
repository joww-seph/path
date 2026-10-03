<?php

use App\Http\Controllers\Office\BusinessVerificationController;
use App\Http\Controllers\Office\DashboardController;
use App\Http\Controllers\Office\EventController;
use App\Http\Controllers\Office\HeritageStoryController;
use App\Http\Controllers\Office\ListingController;
use Illuminate\Support\Facades\Route;

/*
| Municipal Tourism Office area, served under /office with route names prefixed "office.".
| Administrators can also use it.
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('partners', [BusinessVerificationController::class, 'index'])->name('partners.index');
Route::put('partners/{business}', [BusinessVerificationController::class, 'update'])->name('partners.update');

Route::resource('listings', ListingController::class)->except(['show', 'destroy']);
Route::post('listings/{listing}/review', [ListingController::class, 'review'])->name('listings.review');
Route::post('listings/{listing}/archive', [ListingController::class, 'archive'])->name('listings.archive');

Route::scopeBindings()->group(function () {
    Route::post('listings/{listing}/stories', [HeritageStoryController::class, 'store'])->name('listings.stories.store');
    Route::put('listings/{listing}/stories/{heritageStory}', [HeritageStoryController::class, 'update'])->name('listings.stories.update');
    Route::delete('listings/{listing}/stories/{heritageStory}', [HeritageStoryController::class, 'destroy'])->name('listings.stories.destroy');
});

Route::resource('events', EventController::class)->only(['index', 'store', 'update', 'destroy']);
