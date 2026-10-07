<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'start_date' => fake()->dateTimeBetween('-8 years', '-2 years')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
            'is_current' => false,
            'bullets' => [fake()->sentence(), fake()->sentence()],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['is_current' => true, 'end_date' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
