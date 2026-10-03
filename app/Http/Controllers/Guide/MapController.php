<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingCardResource;
use App\Models\Category;
use App\Models\Listing;
use App\Support\Geo;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('guide/Map', [
            'listings' => ListingCardResource::collection(
                Listing::published()->whereNotNull('latitude')->with(['category', 'coverPhoto'])->orderBy('name')->get(),
            )->resolve(),
            'categories' => Category::orderBy('position')->get(['id', 'name', 'slug', 'icon', 'color']),
            'center' => Geo::PAOAY_CENTER,
        ]);
    }
}
