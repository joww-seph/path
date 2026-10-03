<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Simple sales reports for a partner: bookings and earnings per month, and top-selling rates.
 */
class ReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $from = now()->startOfMonth()->subMonths(11);

        $bookings = Booking::forPartner($request->user())
            ->whereDate('date', '>=', $from->toDateString())
            ->get(['id', 'date', 'status', 'total_amount', 'rate_name', 'listing_id']);

        $months = collect(range(0, 11))->map(fn (int $offset) => $from->addMonths($offset)->format('Y-m'));

        $byMonth = $months->map(function (string $month) use ($bookings) {
            $inMonth = $bookings->filter(fn (Booking $booking) => $booking->date->format('Y-m') === $month);

            return [
                'month' => $month,
                'bookings' => $inMonth->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->count(),
                'earnings' => round((float) $inMonth->where('status', BookingStatus::Completed)->sum('total_amount'), 2),
            ];
        });

        return Inertia::render('partner/Reports', [
            'byMonth' => $byMonth,
            'totals' => [
                'requests' => $bookings->count(),
                'completed' => $bookings->where('status', BookingStatus::Completed)->count(),
                'earnings' => round((float) $bookings->where('status', BookingStatus::Completed)->sum('total_amount'), 2),
                'acceptance_rate' => $this->acceptanceRate($bookings),
            ],
            'topRates' => $bookings->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
                ->groupBy('rate_name')
                ->map(fn (Collection $group, string $name) => ['name' => $name, 'bookings' => $group->count(), 'revenue' => round((float) $group->sum('total_amount'), 2)])
                ->sortByDesc('bookings')
                ->take(5)
                ->values(),
        ]);
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function acceptanceRate(Collection $bookings): ?float
    {
        $answered = $bookings->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::NoShow, BookingStatus::Declined]);

        return $answered->isEmpty() ? null : round($answered->where('status', '!==', BookingStatus::Declined)->count() / $answered->count() * 100);
    }
}
