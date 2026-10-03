<?php

namespace App\Services;

use App\Models\Advisory;
use App\Models\Category;
use App\Models\ItineraryItem;
use App\Models\Listing;
use App\Models\Trip;
use App\Models\User;
use App\Support\Geo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Schedules, orders and checks a trip's itinerary.
 */
class ItineraryPlanner
{
    /**
     * Planning beyond this many minutes of visits and travel in one day earns a warning.
     */
    public const LONG_DAY_MINUTES = 10 * 60;

    public const MAX_STOPS_PER_DAY = 8;

    public const LONG_TRAVEL_MINUTES = 60;

    /**
     * When spreading stops across days, aim for this much per day.
     */
    private const TARGET_DAY_MINUTES = 8 * 60;

    /**
     * Which categories suit each travel interest, used to suggest places.
     *
     * @var array<string, list<string>>
     */
    private const INTEREST_CATEGORIES = [
        'heritage' => ['attraction', 'tour'],
        'nature' => ['attraction', 'tour'],
        'adventure' => ['activity', 'tour'],
        'food' => ['food'],
        'beach' => ['attraction', 'activity'],
        'shopping' => ['shop'],
        'photography' => ['attraction', 'activity'],
        'faith' => ['attraction'],
    ];

    public function __construct(
        private RoutingService $routing,
        private WeatherService $weather,
    ) {}

    /**
     * Work out travel legs and start and end times for every stop, day by day.
     */
    public function schedule(Trip $trip): void
    {
        $items = $trip->items()->with('listing')->get();

        foreach ($items->groupBy('day_number') as $dayItems) {
            $clock = self::toMinutes($trip->day_starts_at);
            $previous = null;

            foreach ($dayItems->values() as $index => $item) {
                $item->position = $index;
                $leg = $previous ? $this->leg($previous, $item, $trip) : null;

                $item->travel_minutes_from_previous = $leg['minutes'] ?? null;
                $item->distance_km_from_previous = $leg['km'] ?? null;

                $start = $clock + ($leg['minutes'] ?? 0);

                if ($item->fixed_start_time !== null) {
                    $start = max($start, self::toMinutes($item->fixed_start_time));
                }

                $end = $start + $item->visitMinutes();

                $item->start_time = self::toClock($start);
                $item->end_time = self::toClock($end);
                $this->saveWithoutTouching($item);

                $clock = $end;
                $previous = $item->hasLocation() ? $item : $previous;
            }
        }
    }

    /**
     * Reorder each day's stops to cut travel: nearest neighbour from the day's first stop, then
     * 2-opt to undo crossings. Stops without a location keep their order at the end of the day.
     */
    public function arrange(Trip $trip, ?int $dayNumber = null): void
    {
        $items = $trip->items()->with('listing')->get();

        foreach ($items->groupBy('day_number') as $day => $dayItems) {
            if ($dayNumber !== null && (int) $day !== $dayNumber) {
                continue;
            }

            $this->saveOrder($this->shortestRoute($dayItems->values()));
        }

        $this->schedule($trip);
    }

    /**
     * Spread every stop across the trip's days: follow one short route through all stops, cut it
     * into days of about eight hours, and push a stop to a later day when it is closed on its day.
     */
    public function balance(Trip $trip): void
    {
        $items = $trip->items()->with('listing')->get();

        if ($items->isEmpty()) {
            return;
        }

        $route = $this->shortestRoute($items->sortBy(['day_number', 'position'])->values());
        $days = $trip->dayCount();
        $dailyBudget = max(self::TARGET_DAY_MINUTES, (int) ceil($this->totalMinutes($route, $trip) / $days));

        $day = 1;
        $used = 0;
        $previous = null;

        foreach ($route as $item) {
            $cost = $item->visitMinutes() + ($previous ? ($this->leg($previous, $item, $trip)['minutes'] ?? 0) : 0);

            if ($used > 0 && $used + $cost > $dailyBudget && $day < $days) {
                $day++;
                $used = 0;
                $cost = $item->visitMinutes();
            }

            // A stop closed on this day moves to the next day it is open, without using up this day.
            $item->day_number = $this->firstOpenDay($item, $trip, $day);

            if ($item->day_number === $day) {
                $used += $cost;
                $previous = $item->hasLocation() ? $item : $previous;
            }
        }

        foreach ($route->groupBy('day_number') as $dayItems) {
            $this->saveOrder($this->shortestRoute($dayItems->values()));
        }

        $this->schedule($trip);
    }

    /**
     * Problems with the plan, such as a site that is closed or a day that is too full.
     *
     * @return list<array{day: int, item_id: int|null, type: string, message: string}>
     */
    public function warnings(Trip $trip): array
    {
        $warnings = [];
        $items = $trip->items()->with('listing')->get();

        $advisories = Advisory::with('listings:id')
            ->activeBetween($trip->start_date->startOfDay(), $trip->end_date->endOfDay())
            ->get();

        foreach ($items->groupBy('day_number') as $day => $dayItems) {
            $day = (int) $day;

            if ($day > $trip->dayCount()) {
                $warnings[] = $this->warning($day, null, 'outside_trip', __('Day :day is after your trip ends. Move these stops to another day.', ['day' => $day]));

                continue;
            }

            $date = $trip->dateForDay($day);

            foreach ($dayItems as $item) {
                $listing = $item->listing;

                if ($listing !== null && ! $listing->isOpenOn($date)) {
                    $warnings[] = $this->warning($day, $item->id, 'closed', __(':name is closed on :weekday.', ['name' => $listing->name, 'weekday' => $date->translatedFormat('l')]));
                } elseif ($listing !== null && ($hours = $listing->hoursOn($date)) !== null && $item->start_time !== null) {
                    if (self::toMinutes($item->start_time) < self::toMinutes($hours['open'])) {
                        $warnings[] = $this->warning($day, $item->id, 'before_opening', __(':name opens at :time. You would arrive earlier.', ['name' => $listing->name, 'time' => self::display($hours['open'])]));
                    } elseif (self::toMinutes($item->end_time) > self::toMinutes($hours['close'])) {
                        $warnings[] = $this->warning($day, $item->id, 'after_closing', __(':name closes at :time, before your visit ends.', ['name' => $listing->name, 'time' => self::display($hours['close'])]));
                    }
                }

                if ($item->fixed_start_time !== null && $item->start_time !== null && self::toMinutes($item->start_time) > self::toMinutes($item->fixed_start_time)) {
                    $warnings[] = $this->warning($day, $item->id, 'missed_fixed_time', __('You cannot reach :name by :time with the stops before it.', ['name' => $item->title(), 'time' => self::display($item->fixed_start_time)]));
                }

                if (($item->travel_minutes_from_previous ?? 0) > self::LONG_TRAVEL_MINUTES) {
                    $warnings[] = $this->warning($day, $item->id, 'long_travel', __('Getting to :name takes about :minutes minutes.', ['name' => $item->title(), 'minutes' => $item->travel_minutes_from_previous]));
                }
            }

            foreach ($advisories as $advisory) {
                if (! $this->advisoryCovers($advisory, $date)) {
                    continue;
                }

                $affected = $advisory->listings->pluck('id');

                if ($affected->isEmpty()) {
                    $warnings[] = $this->warning($day, null, 'advisory', __('Advisory: :title', ['title' => $advisory->title]));

                    continue;
                }

                foreach ($dayItems->whereIn('listing_id', $affected) as $item) {
                    $warnings[] = $this->warning($day, $item->id, 'advisory', __('Advisory: :title', ['title' => $advisory->title]));
                }
            }

            if (($forecast = $this->weather->forDate($date->toDateString())) !== null && $forecast['warning'] !== null) {
                $warnings[] = $this->warning($day, null, 'weather', match ($forecast['warning']) {
                    'storm' => __('Strong winds are forecast on day :day. Check PAGASA advisories before going to the dunes or the lake.', ['day' => $day]),
                    'thunderstorm' => __('Thunderstorms are forecast on day :day. Plan indoor stops and avoid open areas.', ['day' => $day]),
                    default => __('Rain is likely on day :day (:chance%). Bring an umbrella and consider indoor stops.', ['day' => $day, 'chance' => $forecast['rain_chance']]),
                });
            }

            if ($dayItems->count() > self::MAX_STOPS_PER_DAY) {
                $warnings[] = $this->warning($day, null, 'too_many_stops', __('Day :day has :count stops. Consider moving some to another day.', ['day' => $day, 'count' => $dayItems->count()]));
            }

            $first = $dayItems->first();
            $last = $dayItems->last();

            if ($first->start_time !== null && $last->end_time !== null) {
                $length = self::toMinutes($last->end_time) - self::toMinutes($trip->day_starts_at);

                if ($length > self::LONG_DAY_MINUTES) {
                    $warnings[] = $this->warning($day, null, 'long_day', __('Day :day runs until :time. That is a long day.', ['day' => $day, 'time' => self::display($last->end_time)]));
                }
            }
        }

        return $warnings;
    }

    private function advisoryCovers(Advisory $advisory, CarbonImmutable $date): bool
    {
        return $advisory->starts_at->lte($date->endOfDay())
            && ($advisory->ends_at === null || $advisory->ends_at->gte($date->startOfDay()));
    }

    /**
     * Published places the traveller has not added yet, ranked by their interests and ratings.
     *
     * @return Collection<int, Listing>
     */
    public function suggestions(Trip $trip, ?User $user, int $limit = 6): Collection
    {
        $profile = $user?->touristProfile;
        $interests = $profile->interests ?? [];
        $needsAccess = in_array('wheelchair', $profile->accessibility_needs ?? [], true);

        $preferred = collect($interests)
            ->flatMap(fn (string $interest) => self::INTEREST_CATEGORIES[$interest] ?? [])
            ->countBy();

        $categoryIds = Category::pluck('slug', 'id');
        $added = $trip->items()->whereNotNull('listing_id')->pluck('listing_id');

        return Listing::published()
            ->whereNotIn('id', $added)
            ->with(['category', 'coverPhoto'])
            ->get()
            ->sortByDesc(fn (Listing $listing) => ($preferred[$categoryIds[$listing->category_id] ?? ''] ?? 0) * 3
                + ($listing->is_featured ? 2 : 0)
                + (float) $listing->rating_average
                + ($needsAccess && $listing->is_accessible ? 2 : 0))
            ->take($limit)
            ->values();
    }

    /**
     * @param  Collection<int, ItineraryItem>  $items
     * @return Collection<int, ItineraryItem>
     */
    private function shortestRoute(Collection $items): Collection
    {
        [$located, $unlocated] = $items->partition(fn (ItineraryItem $item) => $item->hasLocation());

        if ($located->count() < 3) {
            return $located->concat($unlocated)->values();
        }

        $remaining = $located->values()->all();
        $route = [array_shift($remaining)];

        while ($remaining !== []) {
            $last = end($route);
            usort($remaining, fn ($a, $b) => $this->distance($last, $a) <=> $this->distance($last, $b));
            $route[] = array_shift($remaining);
        }

        $route = $this->twoOpt($route);

        return collect($route)->concat($unlocated)->values();
    }

    /**
     * Reverse any stretch of the route that makes it shorter, until nothing improves.
     *
     * @param  list<ItineraryItem>  $route
     * @return list<ItineraryItem>
     */
    private function twoOpt(array $route): array
    {
        $count = count($route);
        $improved = true;

        while ($improved) {
            $improved = false;

            for ($i = 1; $i < $count - 1; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $before = $this->distance($route[$i - 1], $route[$i]) + ($j + 1 < $count ? $this->distance($route[$j], $route[$j + 1]) : 0);
                    $after = $this->distance($route[$i - 1], $route[$j]) + ($j + 1 < $count ? $this->distance($route[$i], $route[$j + 1]) : 0);

                    if ($after + 0.0001 < $before) {
                        array_splice($route, $i, $j - $i + 1, array_reverse(array_slice($route, $i, $j - $i + 1)));
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    private function distance(ItineraryItem $from, ItineraryItem $to): float
    {
        return Geo::distanceKm($from->latitude(), $from->longitude(), $to->latitude(), $to->longitude());
    }

    /**
     * @return array{minutes: int, km: float, estimated: bool}|null
     */
    private function leg(ItineraryItem $from, ItineraryItem $to, Trip $trip): ?array
    {
        if (! $from->hasLocation() || ! $to->hasLocation()) {
            return null;
        }

        return $this->routing->between($from->latitude(), $from->longitude(), $to->latitude(), $to->longitude(), $trip->travel_mode);
    }

    /**
     * @param  Collection<int, ItineraryItem>  $route
     */
    private function totalMinutes(Collection $route, Trip $trip): int
    {
        $total = 0;
        $previous = null;

        foreach ($route as $item) {
            $total += $item->visitMinutes() + ($previous ? ($this->leg($previous, $item, $trip)['minutes'] ?? 0) : 0);
            $previous = $item->hasLocation() ? $item : $previous;
        }

        return $total;
    }

    private function firstOpenDay(ItineraryItem $item, Trip $trip, int $fromDay): int
    {
        if ($item->listing === null) {
            return $fromDay;
        }

        for ($day = $fromDay; $day <= $trip->dayCount(); $day++) {
            if ($item->listing->isOpenOn($trip->dateForDay($day))) {
                return $day;
            }
        }

        return $fromDay;
    }

    /**
     * @param  Collection<int, ItineraryItem>  $items
     */
    private function saveOrder(Collection $items): void
    {
        foreach ($items->values() as $position => $item) {
            $item->position = $position;
            $this->saveWithoutTouching($item);
        }
    }

    /**
     * Save worked-out fields without bumping updated_at, which offline sync uses to tell
     * a traveller's own edits apart (last write wins).
     */
    private function saveWithoutTouching(ItineraryItem $item): void
    {
        $item->timestamps = false;
        $item->saveQuietly();
        $item->timestamps = true;
    }

    /**
     * @return array{day: int, item_id: int|null, type: string, message: string}
     */
    private function warning(int $day, ?int $itemId, string $type, string $message): array
    {
        return ['day' => $day, 'item_id' => $itemId, 'type' => $type, 'message' => $message];
    }

    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }

    /**
     * Minutes after midnight as "HH:MM", capped at 23:59 so it fits a TIME column.
     */
    public static function toClock(int $minutes): string
    {
        $minutes = min($minutes, 23 * 60 + 59);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private static function display(string $time): string
    {
        return date('g:i A', strtotime($time));
    }
}
