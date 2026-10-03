<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The partner's booking inbox.
 */
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'when' => ['nullable', Rule::in(['upcoming', 'past'])],
        ]);

        $status = $filters['status'] ?? BookingStatus::Pending->value;
        $base = Booking::forPartner($request->user());

        $bookings = (clone $base)
            ->with(['listing', 'tourist', 'trip'])
            ->where('status', $status)
            ->when(($filters['when'] ?? null) === 'upcoming', fn ($query) => $query->whereDate('date', '>=', now()->toDateString()))
            ->when(($filters['when'] ?? null) === 'past', fn ($query) => $query->whereDate('date', '<', now()->toDateString()))
            ->orderBy($status === BookingStatus::Pending->value ? 'expires_at' : 'date', in_array($status, ['pending', 'confirmed'], true) ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('partner/bookings/Index', [
            'bookings' => BookingResource::collection($bookings),
            'status' => $status,
            'counts' => (clone $base)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'filters' => (object) $filters,
        ]);
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('respond', $booking);

        $this->guard(fn () => $this->bookings->confirm($booking, $request->user()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking :code confirmed. The tourist has their QR voucher.', ['code' => $booking->code])]);

        return back();
    }

    public function decline(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('respond', $booking);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->guard(fn () => $this->bookings->decline($booking, $request->user(), $validated['reason']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking :code declined.', ['code' => $booking->code])]);

        return back();
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('respond', $booking);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->bookings->cancel($booking, $request->user(), $validated['reason']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking :code cancelled.', ['code' => $booking->code])]);

        return back();
    }

    /**
     * Turn an invalid state change (e.g. a request that expired a moment ago) into a form error.
     */
    private function guard(callable $action): void
    {
        try {
            $action();
        } catch (\LogicException) {
            throw ValidationException::withMessages([
                'booking' => __('This booking has already changed. Refresh the page to see its current status.'),
            ]);
        }
    }
}
