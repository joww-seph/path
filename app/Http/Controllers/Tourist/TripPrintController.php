<?php

namespace App\Http\Controllers\Tourist;

use App\Actions\Trips\BuildPlannerView;
use App\Http\Controllers\Controller;
use App\Models\Hotline;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TripPrintController extends Controller
{
    /**
     * Download the itinerary as a PDF to print or keep on the phone.
     */
    public function __invoke(Request $request, Trip $trip, BuildPlannerView $view): Response
    {
        Gate::authorize('view', $trip);

        $data = $view->handle($trip, $request);

        return Pdf::loadView('trips.itinerary-pdf', [...$data, 'hotlines' => Hotline::orderBy('position')->get()])
            ->setPaper('a4')
            ->download(Str::slug($trip->title).'-itinerary.pdf');
    }
}
