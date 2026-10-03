<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Checking tourists in by scanning their voucher QR code or typing the booking code.
 */
class CheckInController extends Controller
{
    public function scanner(): Response
    {
        return Inertia::render('partner/bookings/Scanner');
    }

    /**
     * The page a voucher's QR code opens: shows the booking so the partner can confirm arrival.
     */
    public function show(string $token): Response
    {
        $booking = Booking::where('qr_token', $token)->with(['listing', 'tourist'])->firstOrFail();

        Gate::authorize('respond', $booking);

        return Inertia::render('partner/bookings/CheckIn', [
            'booking' => (new BookingResource($booking))->resolve(),
            'token' => $token,
        ]);
    }

    public function store(Request $request, BookingService $bookings): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required_without:token', 'nullable', 'string', 'max:20'],
            'token' => ['required_without:code', 'nullable', 'string', 'max:64'],
        ]);

        $booking = isset($validated['token'])
            ? Booking::where('qr_token', $validated['token'])->first()
            : Booking::where('code', Str::upper(trim($validated['code'])))->first();

        if ($booking === null || Gate::denies('respond', $booking)) {
            throw ValidationException::withMessages(['code' => __('No booking for your listings matches that code.')]);
        }

        if ($booking->date->toDateString() !== now()->toDateString()) {
            throw ValidationException::withMessages(['code' => __('Booking :code is for :date, not today.', ['code' => $booking->code, 'date' => $booking->date->format('F j, Y')])]);
        }

        $bookings->checkIn($booking, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name checked in. Booking :code is complete.', ['name' => $booking->tourist->name, 'code' => $booking->code])]);

        return to_route('partner.bookings.index', ['status' => 'completed']);
    }
}
