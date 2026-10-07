<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'subtitle' => fake()->sentence(4),
            'video_url' => null,
            'tech' => ['PHP', 'Laravel'],
            'bullets' => [fake()->sentence(), fake()->sentence()],
            'links' => [['label' => 'Source', 'url' => fake()->url()]],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
