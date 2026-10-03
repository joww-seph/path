<?php

namespace App\Http\Controllers\Office;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\SiteVisit;
use App\Models\TouristProfile;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Visitor analytics for the tourism office: who is coming, when, where they go and where from.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('office/Analytics', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            ...$this->report($from, $to),
        ]);
    }

    /**
     * The daily figures as a CSV file for spreadsheets and reports.
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $report = $this->report($from, $to);

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'tourists_in_paoay', 'site_visits', 'bookings']);

            foreach ($report['daily'] as $row) {
                fputcsv($out, [$row['date'], $row['tourists'], $row['visits'], $row['bookings']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['top_site', 'visits', 'planned']);

            foreach ($report['topSites'] as $row) {
                fputcsv($out, [$row['name'], $row['visits'], $row['planned']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['visitor_origin', 'tourists']);

            foreach ($report['origins'] as $row) {
                fputcsv($out, [$row['origin'], $row['total']]);
            }

            fclose($out);
        }, "path-analytics-{$from->toDateString()}-to-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to']) : now()->addDays(30)->startOfDay();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from']) : $to->subDays(89);

        // Keep reports to a year so the daily table stays readable.
        return [$from->max($to->subDays(365)), $to];
    }

    /**
     * @return array<string, mixed>
     */
    private function report(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $trips = Trip::with('members:id')
            ->where('start_date', '<=', $to->toDateString().' 23:59:59')
            ->where('end_date', '>=', $from->toDateString())
            ->get(['id', 'user_id', 'start_date', 'end_date', 'pax']);

        $visits = SiteVisit::whereBetween('visited_on', [$from->toDateString(), $to->toDateString()])->get(['listing_id', 'user_id', 'visited_on']);

        $bookings = Booking::whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['id', 'date', 'status', 'total_amount']);

        $daily = collect();

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $date = $day->toDateString();

            $daily->push([
                'date' => $date,
                // Tourists in town: the travellers on trips that include this day.
                'tourists' => (int) $trips->filter(fn (Trip $trip) => $trip->start_date->toDateString() <= $date && $trip->end_date->toDateString() >= $date)->sum('pax'),
                'visits' => $visits->where('visited_on', $date)->count(),
                'bookings' => $bookings->filter(fn (Booking $booking) => $booking->date->toDateString() === $date && $booking->status !== BookingStatus::Declined)->count(),
            ]);
        }

        return [
            'totals' => [
                'trips' => $trips->count(),
                'travellers' => (int) $trips->sum('pax'),
                'visits' => $visits->count(),
                'bookings' => $bookings->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->count(),
                'booking_value' => round((float) $bookings->where('status', BookingStatus::Completed)->sum('total_amount'), 2),
            ],
            'daily' => $daily,
            'peakDates' => $daily->sortByDesc('tourists')->filter(fn ($row) => $row['tourists'] > 0)->take(5)->values(),
            'topSites' => $this->topSites($trips, $visits),
            'origins' => $this->origins($trips),
            'bookingsByStatus' => $bookings->groupBy(fn (Booking $booking) => $booking->status->value)
                ->map(fn (Collection $group, string $status) => ['status' => $status, 'label' => BookingStatus::from($status)->label(), 'total' => $group->count()])
                ->values(),
        ];
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @param  Collection<int, SiteVisit>  $visits
     * @return Collection<int, array{name: string, slug: string, visits: int, planned: int}>
     */
    private function topSites(Collection $trips, Collection $visits): Collection
    {
        $planned = ItineraryItem::whereIn('trip_id', $trips->pluck('id'))->whereNotNull('listing_id')
            ->toBase()->selectRaw('listing_id, count(*) as total')->groupBy('listing_id')->pluck('total', 'listing_id');
        $visited = $visits->countBy('listing_id');

        $ids = $planned->keys()->merge($visited->keys())->unique();

        return Listing::whereIn('id', $ids)->get(['id', 'name', 'slug'])
            ->map(fn (Listing $listing) => [
                'name' => $listing->name,
                'slug' => $listing->slug,
                'visits' => (int) ($visited[$listing->id] ?? 0),
                'planned' => (int) ($planned[$listing->id] ?? 0),
            ])
            ->sortByDesc(fn ($row) => [$row['visits'], $row['planned']])
            ->take(10)
            ->values();
    }

    /**
     * Where trip owners come from, by province (Philippines) or country.
     *
     * @param  Collection<int, Trip>  $trips
     * @return Collection<int, array{origin: string, total: int}>
     */
    private function origins(Collection $trips): Collection
    {
        $userIds = $trips->pluck('user_id')->merge($trips->flatMap(fn (Trip $trip) => $trip->members->pluck('id')))->unique();

        return TouristProfile::whereIn('user_id', $userIds)->get(['home_province', 'home_country'])
            ->map(fn (TouristProfile $profile) => $profile->home_country !== 'PH'
                ? $profile->home_country
                : ($profile->home_province ? trim($profile->home_province) : 'Philippines (province not given)'))
            ->countBy()
            ->map(fn (int $total, string $origin) => ['origin' => $origin, 'total' => $total])
            ->sortByDesc('total')
            ->values();
    }
}
