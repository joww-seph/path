<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\RateUnit;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Seeder;

/**
 * Sample businesses for local development and demos, labelled as demo data.
 * Replace them with the listings gathered from real partners (gameplan Phase 0).
 */
class DemoListingsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $daily = fn (string $open, string $close) => array_fill_keys(Listing::WEEKDAYS, ['open' => $open, 'close' => $close]);

        $dunes = Business::where('name', 'Paoay Dunes 4x4 Adventures')->first();

        if ($dunes !== null) {
            $ride = $this->listing($categories['activity'], ListingStatus::Published, $dunes, [
                'name' => 'Sand Dunes 4x4 Ride and Sandboarding',
                'summary' => 'Demo listing. A 45-minute 4x4 ride over the Suba dunes with a sandboarding stop.',
                'description' => "Our drivers take you over the steepest dunes in Suba, with stops for photos and sandboarding. Rides leave every hour from 6:00 AM; the best light is before 8:00 AM and after 4:00 PM.\n\nThis is sample data for the PaTH demo.",
                'barangay' => 'suba',
                'address' => 'Brgy. Suba, Paoay, Ilocos Norte',
                'latitude' => 18.0940,
                'longitude' => 120.5030,
                'opening_hours' => $daily('06:00', '18:00'),
                'price_min' => 2500,
                'price_max' => 3500,
                'visit_minutes' => 90,
                'contact_phone' => '09181234567',
                'is_bookable' => true,
            ]);

            foreach ([
                ['name' => '4x4 ride, up to 5 people', 'price' => 2500, 'unit' => RateUnit::PerRide, 'capacity' => 5],
                ['name' => '4x4 ride with sandboarding, up to 5 people', 'price' => 3500, 'unit' => RateUnit::PerRide, 'capacity' => 5],
                ['name' => 'Sandboard rental', 'price' => 200, 'unit' => RateUnit::PerItem, 'capacity' => null],
            ] as $position => $rate) {
                $ride->rates()->updateOrCreate(['name' => $rate['name']], [...$rate, 'position' => $position]);
            }

            $this->listing($categories['tour'], ListingStatus::Pending, $dunes, [
                'name' => 'Sunset Lake and Dunes Tour',
                'summary' => 'Demo listing awaiting tourism office approval.',
                'description' => 'A half-day tour of Paoay Lake, the Malacañang of the North and the dunes at sunset. Sample data for the PaTH demo.',
                'barangay' => 'suba',
                'latitude' => 18.1100,
                'longitude' => 120.5300,
                'price_min' => 1500,
                'visit_minutes' => 240,
                'is_bookable' => true,
            ]);
        }

        $samples = [
            ['food', 'Plaza Ilocano Kitchen', 'Demo listing. Ilocano home cooking across from the church plaza: pinakbet, poqui-poqui and bagnet.', 18.0612, 120.5225, 150, 450, $daily('07:00', '20:00'), true],
            ['food', 'Lakeview Empanada Stall', 'Demo listing. Crisp orange Ilocos empanada with longganisa and egg, fried to order.', 18.1090, 120.5380, 60, 120, $daily('15:00', '22:00'), false],
            ['lodging', 'Heritage Row Inn', 'Demo listing. Small inn a short walk from Paoay Church, with family rooms and parking.', 18.0630, 120.5200, 1800, 4500, null, true],
            ['shop', 'Paoay Pasalubong Corner', 'Demo listing. Chichacorn, garlic, bagnet, basi wine and inabel weaves to take home.', 18.0620, 120.5235, 50, 1500, $daily('08:00', '19:00'), true],
            ['transport', 'Paoay Tricycle Terminal', 'Demo listing. Tricycles to the church, the lake and the dunes. Agree on the fare before you ride.', 18.0605, 120.5240, 20, 300, $daily('05:00', '21:00'), false],
        ];

        foreach ($samples as [$category, $name, $summary, $lat, $lng, $min, $max, $hours, $accessible]) {
            $this->listing($categories[$category], ListingStatus::Published, null, [
                'name' => $name,
                'summary' => $summary,
                'description' => $summary."\n\nThis is sample data for the PaTH demo.",
                'latitude' => $lat,
                'longitude' => $lng,
                'price_min' => $min,
                'price_max' => $max,
                'opening_hours' => $hours,
                'is_accessible' => $accessible,
                'visit_minutes' => $category === 'food' ? 60 : 30,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function listing(int $categoryId, ListingStatus $status, ?Business $business, array $attributes): Listing
    {
        $listing = Listing::firstOrNew(['name' => $attributes['name']]);
        $listing->fill([...$attributes, 'category_id' => $categoryId]);
        $listing->forceFill([
            'business_id' => $business?->id,
            'status' => $status,
            'published_at' => $status === ListingStatus::Published ? ($listing->published_at ?? now()) : null,
        ])->save();

        return $listing;
    }
}
