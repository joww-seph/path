<?php

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Enums\VerificationStatus;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->partner(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(BusinessType::cases()),
            'permit_no' => 'BP-'.fake()->numerify('2026-#####'),
            'contact_phone' => '09'.fake()->numerify('#########'),
            'contact_email' => fake()->companyEmail(),
            'address' => 'Brgy. '.fake()->randomElement(['Pasil', 'Suba', 'Callaguip', 'Nagbacalan']).', Paoay, Ilocos Norte',
            'description' => fake()->sentence(12),
            'verification_status' => VerificationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Rejected,
            'verification_note' => 'Business permit could not be verified.',
        ]);
    }
}
