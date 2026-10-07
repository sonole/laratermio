<?php

namespace Database\Factories;

use App\Models\TerminalCommand;
use App\Terminal\Commands\WhoamiCommand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TerminalCommand>
 */
class TerminalCommandFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('cmd????'),
            'command_class' => WhoamiCommand::class,
            'display_label' => fake()->word(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'interaction_type' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
