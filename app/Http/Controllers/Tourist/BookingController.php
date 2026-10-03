<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\ListingRate;
use App\Models\Trip;
use App\Services\BookingService;
use App\Support\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): Response
    {
        $bookings = $request->user()->bookings()
            ->with('listing')
            ->orderByRaw("case when status in ('pending', 'confirmed') then 0 else 1 end")
            ->orderBy('date')
            ->get();

        return Inertia::render('tourist/bookings/Index', [
            'bookings' => BookingResource::collection($bookings)->resolve(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'listing_id' => ['required', 'integer', Rule::exists('listings', 'id')],
            'listing_rate_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:+1 year'],
            'time' => ['nullable', 'date_format:H:i'],
            'nights' => ['nullable', 'integer', 'min:1', 'max:14'],
            'pax' => ['required', 'integer', 'min:1', 'max:100'],
            'trip_id' => ['nullable', 'integer', Rule::exists('trips', 'id')],
            'tourist_note' => ['nullable', 'string', 'max:500'],
        ]);

        $listing = Listing::findOrFail($validated['listing_id']);
        $rate = ListingRate::whereKey($validated['listing_rate_id'])->where('listing_id', $listing->id)->first();

        if ($rate === null) {
            return back()->withErrors(['listing_rate_id' => __('Choose one of this listing\'s rates.')]);
        }

        if (isset($validated['trip_id'])) {
            Gate::authorize('update', Trip::findOrFail($validated['trip_id']));
        }

        $booking = $this->bookings->request($request->user(), $listing, $rate, collect($validated)->except(['listing_id', 'listing_rate_id'])->all());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking requested. :listing has :hours hours to confirm.', [
            'listing' => $listing->name,
            'hours' => Booking::RESPONSE_HOURS,
        ])]);

        return to_route('tourist.bookings.show', $booking);
    }

    /**
     * The booking voucher: details, payment instructions and, once confirmed, the QR code.
     */
    public function show(Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $booking->load(['listing.business', 'trip']);

        return Inertia::render('tourist/bookings/Show', [
            'booking' => (new BookingResource($booking))->resolve(),
            'qr' => $this->qr($booking),
            'paymentInstructions' => $booking->status === BookingStatus::Confirmed ? $booking->listing->business?->payment_instructions : null,
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Booking cancelled.')]);

        return back();
    }

    public function pdf(Booking $booking): HttpResponse
    {
        Gate::authorize('view', $booking);
        abort_unless(in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed], true), 404);

        $booking->load('listing.business');

        return Pdf::loadView('bookings.voucher-pdf', ['booking' => $booking, 'qr' => $this->qr($booking)])
            ->setPaper('a5')
            ->download("{$booking->code}.pdf");
    }

    private function qr(Booking $booking): ?string
    {
        return $booking->qr_token && $booking->status === BookingStatus::Confirmed
            ? QrCode::svg(route('partner.check-in.show', $booking->qr_token))
            : null;
    }
}
