<?php

namespace Database\Factories;

use App\Enums\NavItemType;
use App\Models\NavItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavItem>
 */
class NavItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'terminal_command_id' => null,
            'command_args' => null,
            'label' => fake()->word(),
            'url' => fake()->url(),
            'target' => '_blank',
            'type' => NavItemType::Link,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
