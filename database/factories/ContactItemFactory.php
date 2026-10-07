<?php

namespace Database\Factories;

use App\Models\ContactItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactItem>
 */
class ContactItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'icon' => 'fa-solid fa-envelope',
            'label' => fake()->safeEmail(),
            'url' => 'mailto:'.fake()->safeEmail(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
