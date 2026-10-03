<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * The events calendar for one month, plus the next few upcoming events.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();

        return Inertia::render('guide/Events', [
            'month' => $month->format('Y-m'),
            'events' => Event::with('venue:id,name,slug')
                ->between($month, $month->endOfMonth())
                ->orderBy('starts_at')
                ->get(),
            'upcoming' => Event::with('venue:id,name,slug')->upcoming()->orderBy('starts_at')->limit(5)->get(),
        ]);
    }

    public function show(Event $event): Response
    {
        return Inertia::render('guide/Event', [
            'event' => $event->load('venue:id,name,slug,address,latitude,longitude'),
        ]);
    }
}
