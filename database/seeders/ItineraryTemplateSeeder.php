<?php

namespace Database\Seeders;

use App\Models\ItineraryTemplate;
use App\Models\Listing;
use Illuminate\Database\Seeder;

/**
 * Ready-made plans tourists can start a trip from.
 */
class ItineraryTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->template('paoay-in-one-day', 'Paoay in One Day', 'The church, the lake and the dunes in a single day, with time for an Ilocano lunch.', 1, [
            [1, 'paoay-church', 60, 'Go early, before the tour buses arrive. Mass times are posted at the door.'],
            [1, 'malacanang-of-the-north', 75, null],
            [1, null, 60, 'Try bagnet, pinakbet or Ilocos empanada.', 'Ilocano lunch'],
            [1, 'paoay-lake', 45, 'Stop at a lakeside viewpoint for photos.'],
            [1, 'paoay-sand-dunes', 120, 'Book a 4x4 ride for mid-afternoon, when the light is softer.'],
        ]);

        $this->template('heritage-and-dunes-weekend', 'Heritage and Dunes Weekend', 'Two unhurried days: heritage and the lake on day one, the dunes, an Ilocano lunch and sunset at the church on day two.', 2, [
            [1, 'paoay-church', 75, 'Walk around the church to see all twenty-four buttresses.'],
            [1, 'paoay-lake', 60, null],
            [1, 'malacanang-of-the-north', 90, null],
            [2, 'paoay-sand-dunes', 150, 'Sunrise rides are cooler and less crowded.'],
            [2, null, 60, 'Lunch: try bagnet, pinakbet or Ilocos empanada.', 'Ilocano lunch'],
            [2, 'paoay-church', 30, 'Return for sunset photos at the church plaza.'],
        ]);
    }

    /**
     * @param  list<array{0: int, 1: string|null, 2: int, 3: string|null, 4?: string}>  $stops
     */
    private function template(string $slug, string $name, string $summary, int $days, array $stops): void
    {
        $template = ItineraryTemplate::updateOrCreate(['slug' => $slug], [
            'name' => $name,
            'summary' => $summary,
            'days' => $days,
        ]);

        $template->items()->delete();
        $listings = Listing::whereIn('slug', array_filter(array_column($stops, 1)))->pluck('id', 'slug');

        foreach ($stops as $position => $stop) {
            [$day, $listingSlug, $minutes, $notes] = $stop;

            if ($listingSlug !== null && ! isset($listings[$listingSlug])) {
                continue;
            }

            $template->items()->create([
                'listing_id' => $listingSlug ? $listings[$listingSlug] : null,
                'custom_title' => $stop[4] ?? null,
                'day_number' => $day,
                'position' => $position,
                'duration_minutes' => $minutes,
                'notes' => $notes,
            ]);
        }
    }
}
