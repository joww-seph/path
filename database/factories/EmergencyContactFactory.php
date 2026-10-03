<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyContact>
 */
class EmergencyContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['Parent', 'Sibling', 'Spouse', 'Friend']),
            'phone' => '09'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
        ];
    }
}
