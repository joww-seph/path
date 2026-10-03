<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Show the interactive barangay map of Paoay.
     */
    public function __invoke(): View
    {
        $map = json_decode(file_get_contents(resource_path('data/paoay-map.json')), true);

        $barangays = collect($map['barangays'])->map(function (array $barangay) {
            $barangay['url'] = config("barangays.facebook.{$barangay['slug']}")
                ?? 'https://www.facebook.com/search/pages/?q='.rawurlencode("Barangay {$barangay['name']} Paoay Ilocos Norte");

            // Where the piece starts before it slides into place.
            $seed = crc32($barangay['slug']);
            $angle = deg2rad($seed % 360);
            $barangay['scatter'] = [
                'x' => round(cos($angle) * (90 + $seed % 60), 1),
                'y' => round(sin($angle) * (90 + ($seed >> 8) % 60), 1),
                'r' => ($seed >> 4) % 50 - 25,
            ];

            return $barangay;
        });

        return view('home', [
            'viewBox' => implode(' ', $map['viewBox']),
            'silhouette' => $map['silhouette'],
            'lake' => $map['lake'],
            'lakeLabel' => $map['lakeLabel'],
            'barangays' => $barangays,
        ]);
    }
}
