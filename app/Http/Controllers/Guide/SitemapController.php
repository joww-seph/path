<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Listing;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('explore'), 'lastmod' => null],
            ['loc' => route('map'), 'lastmod' => null],
            ['loc' => route('events.index'), 'lastmod' => null],
        ])
            ->merge(Listing::published()->get(['slug', 'updated_at'])->map(fn (Listing $listing) => [
                'loc' => route('listings.show', $listing),
                'lastmod' => $listing->updated_at?->toAtomString(),
            ]))
            ->merge(Event::upcoming()->get(['slug', 'updated_at'])->map(fn (Event $event) => [
                'loc' => route('events.show', $event),
                'lastmod' => $event->updated_at?->toAtomString(),
            ]));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
