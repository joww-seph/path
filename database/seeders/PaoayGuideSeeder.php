<?php

namespace Database\Seeders;

use App\Enums\HotlineType;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\Hotline;
use App\Models\Listing;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Database\Seeder;

/**
 * Paoay's main public sites, festivals and national hotlines.
 *
 * Coordinates are approximate, and hours and fees must be confirmed with the
 * Municipal Tourism Office before the pilot (gameplan Phase 0). The office can
 * correct any of them from its dashboard.
 */
class PaoayGuideSeeder extends Seeder
{
    public function run(): void
    {
        $attraction = Category::where('slug', 'attraction')->firstOrFail();
        $daily = fn (string $open, string $close) => array_fill_keys(Listing::WEEKDAYS, ['open' => $open, 'close' => $close]);

        $church = $this->listing($attraction, [
            'name' => 'San Agustin Church (Paoay Church)',
            'slug' => 'paoay-church',
            'summary' => 'A UNESCO World Heritage Site and the best-known example of "Earthquake Baroque" in the Philippines.',
            'description' => "San Agustin Church is part of the UNESCO World Heritage Site \"Baroque Churches of the Philippines\". Its massive side buttresses and separate coral-stone bell tower were built to withstand earthquakes.\n\nThe church is an active parish. Dress modestly and keep quiet during Mass. The plaza in front is a favourite spot for photos, especially in the late afternoon.",
            'address' => 'Marcos Avenue, Paoay, Ilocos Norte',
            'latitude' => 18.0617,
            'longitude' => 120.5214,
            'opening_hours' => $daily('06:00', '19:00'),
            'entrance_fee' => 0,
            'visit_minutes' => 45,
            'is_accessible' => true,
            'is_featured' => true,
        ]);
        $church->heritageStories()->updateOrCreate(['title' => 'Built to survive earthquakes'], [
            'body' => "Augustinian friars began the present church in 1694 and finished it in 1710. Its walls are braced by twenty-four carved buttresses, a design now called Earthquake Baroque.\n\nThe coral-stone bell tower stands apart from the church so that it would not bring the church down if it fell. It served as a lookout during the revolution against Spain in 1898 and again during the Second World War.\n\nThe church was declared a National Cultural Treasure in 1973 and inscribed on the UNESCO World Heritage List in 1993.",
            'source' => 'UNESCO World Heritage Centre; National Museum of the Philippines',
        ]);

        $lake = $this->listing($attraction, [
            'name' => 'Paoay Lake',
            'slug' => 'paoay-lake',
            'summary' => 'A wide, quiet lake north of the town centre, ringed by hills and good for sunsets and slow drives.',
            'description' => 'Paoay Lake is the largest lake in Ilocos Norte. A road follows much of its shore, with viewpoints, small eateries and the Malacañang of the North along the way.',
            'latitude' => 18.1167,
            'longitude' => 120.5500,
            'opening_hours' => null,
            'entrance_fee' => 0,
            'visit_minutes' => 60,
            'is_featured' => true,
        ]);
        $lake->heritageStories()->updateOrCreate(['title' => 'The legend of the sunken town'], [
            'body' => 'A well-known local legend says the lake covers a rich, proud town that sank into the earth as a punishment, and that on calm days the roofs of its houses can still be seen beneath the water.',
            'source' => 'Ilocano folk tradition',
        ]);

        $palace = $this->listing($attraction, [
            'name' => 'Malacañang of the North',
            'slug' => 'malacanang-of-the-north',
            'summary' => 'The former Marcos family residence on the shore of Paoay Lake, now a museum.',
            'description' => 'A large two-storey house overlooking Paoay Lake, built as the official residence of President Ferdinand E. Marcos in Ilocos Norte. It is now a museum run by the provincial government, with period furniture, photographs and wide lawns facing the lake.',
            'barangay' => 'suba',
            'address' => 'Brgy. Suba, Paoay, Ilocos Norte',
            'latitude' => 18.1142,
            'longitude' => 120.5411,
            'opening_hours' => $daily('09:00', '16:00'),
            'visit_minutes' => 60,
            'is_featured' => true,
        ]);
        $palace->heritageStories()->updateOrCreate(['title' => 'A house on the lake'], [
            'body' => 'The residence was built for the Marcos family during the presidency of Ferdinand E. Marcos, who was born in nearby Sarrat. After 1986 it passed to the provincial government, which opened it to visitors as a museum.',
            'source' => 'Provincial Government of Ilocos Norte',
        ]);

        $this->listing($attraction, [
            'name' => 'Paoay Sand Dunes',
            'slug' => 'paoay-sand-dunes',
            'summary' => 'Rolling coastal dunes known for 4x4 rides and sandboarding.',
            'description' => "The Paoay Sand Dunes stretch along the coast in Barangay Suba. Accredited operators run 4x4 rides over the dunes and rent sandboards.\n\nGo early in the morning or late in the afternoon: the sand gets very hot at midday and there is little shade. Bring water, sunglasses and a cloth to cover your face from blowing sand. Mobile signal can be weak here, so save your trip offline before you go.",
            'barangay' => 'suba',
            'address' => 'Brgy. Suba, Paoay, Ilocos Norte',
            'latitude' => 18.0926,
            'longitude' => 120.5004,
            'opening_hours' => $daily('06:00', '18:00'),
            'visit_minutes' => 120,
            'is_featured' => true,
        ]);

        $this->events($church);

        foreach ([
            ['name' => 'National emergency hotline', 'type' => HotlineType::Emergency, 'phone' => '911', 'description' => 'Police, fire and medical emergencies anywhere in the Philippines.'],
            ['name' => 'Philippine Red Cross', 'type' => HotlineType::Rescue, 'phone' => '143', 'description' => 'Ambulance, rescue and disaster response.'],
        ] as $position => $hotline) {
            Hotline::updateOrCreate(['phone' => $hotline['phone']], [...$hotline, 'position' => $position]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function listing(Category $category, array $attributes): Listing
    {
        $listing = Listing::firstOrNew(['slug' => $attributes['slug']]);
        $listing->fill($attributes);
        $listing->forceFill([
            'slug' => $attributes['slug'],
            'category_id' => $category->id,
            'is_featured' => $attributes['is_featured'] ?? false,
            'status' => ListingStatus::Published,
            'published_at' => $listing->published_at ?? now(),
        ])->save();

        return $listing;
    }

    private function events(Listing $church): void
    {
        $easter = fn (int $year) => Carbon::create($year, 3, 21)->addDays(easter_days($year));
        $ashWednesday = fn (int $year) => $easter($year)->subDays(46);
        $lentYear = $ashWednesday(now()->year)->isPast() ? now()->year + 1 : now()->year;
        $fiesta = Carbon::create(now()->year, 8, 28);
        $fiesta = $fiesta->endOfDay()->isPast() ? $fiesta->addYear() : $fiesta;

        $events = [
            [
                'title' => 'Guling-Guling Festival',
                'description' => 'Held on the eve of Ash Wednesday, Guling-Guling marks the start of Lent. Townspeople in traditional inabel dress dance through the streets, and elders mark foreheads with a cross of white rice flour.',
                'venue_name' => 'Streets of Paoay town centre',
                'starts_at' => $ashWednesday($lentYear)->subDay()->setTime(8, 0),
                'ends_at' => $ashWednesday($lentYear)->subDay()->setTime(18, 0),
                'is_featured' => true,
            ],
            [
                'title' => 'Holy Week at Paoay Church',
                'description' => 'Paoay Church draws large crowds for Visita Iglesia and the Holy Week processions. Expect heavy traffic and full parking near the church; arrive early or walk in from the edge of town.',
                'venue_listing_id' => $church->id,
                'starts_at' => $easter($lentYear)->subDays(7)->setTime(6, 0),
                'ends_at' => $easter($lentYear)->setTime(12, 0),
                'is_featured' => true,
            ],
            [
                'title' => 'Feast of San Agustin',
                'description' => 'The town fiesta in honour of Saint Augustine, patron of Paoay, with Masses, a procession and celebrations around the church plaza.',
                'venue_listing_id' => $church->id,
                'starts_at' => $fiesta->setTime(6, 0),
                'ends_at' => $fiesta->setTime(22, 0),
                'is_featured' => false,
            ],
        ];

        foreach ($events as $attributes) {
            $event = Event::firstOrNew(['title' => $attributes['title'], 'starts_at' => $attributes['starts_at']]);
            $event->fill($attributes)->save();
        }
    }
}
