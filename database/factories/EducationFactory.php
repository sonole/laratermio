<?php

namespace Database\Factories;

use App\Models\Education;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'institution' => fake()->company(),
            'start_date' => fake()->dateTimeBetween('-12 years', '-8 years')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-8 years', '-4 years')->format('Y-m-d'),
            'is_certification' => false,
            'description' => fake()->sentence(),
            'certificate_url' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function certification(): static
    {
        return $this->state(fn () => ['is_certification' => true, 'end_date' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
