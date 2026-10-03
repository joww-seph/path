<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\RateUnit;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'listing_id' => Listing::factory()->forBusiness(),
            'rate_name' => '4x4 ride, up to 5 people',
            'unit_price' => 2500,
            'unit' => RateUnit::PerRide,
            'date' => now()->addWeek()->toDateString(),
            'time' => '08:00',
            'pax' => 4,
            'quantity' => 1,
            'total_amount' => 2500,
            'status' => BookingStatus::Pending,
            'expires_at' => now()->addHours(Booking::RESPONSE_HOURS),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
            'qr_token' => bin2hex(random_bytes(16)),
        ]);
    }
}
