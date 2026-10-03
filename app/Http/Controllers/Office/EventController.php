<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('office/Events', [
            'events' => Event::with('venue:id,name')->orderByDesc('starts_at')->paginate(20),
            'venues' => Listing::published()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = new Event($this->validated($request));
        $event->created_by = $request->user()->id;
        $event->save();

        ActivityLog::record('event.created', $event);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event added to the calendar.')]);

        return back();
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->validated($request));

        ActivityLog::record('event.updated', $event);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event saved.')]);

        return back();
    }

    public function destroy(Event $event): RedirectResponse
    {
        ActivityLog::record('event.deleted', $event, ['title' => $event->title]);

        $event->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $request->merge(['is_featured' => $request->boolean('is_featured')]);

        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'venue_listing_id' => ['nullable', 'integer', Rule::exists('listings', 'id')],
            'venue_name' => ['nullable', 'string', 'max:150', 'required_without:venue_listing_id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_featured' => ['boolean'],
        ]);
    }
}
