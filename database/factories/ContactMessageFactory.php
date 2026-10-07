<?php

namespace Database\Factories;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'message' => fake()->paragraph(),
            'visitor_status' => ContactMessageStatus::Pending,
            'admin_status' => ContactMessageStatus::Pending,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'visitor_status' => ContactMessageStatus::Sent,
            'admin_status' => ContactMessageStatus::Sent,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'visitor_status' => ContactMessageStatus::Failed,
            'admin_status' => ContactMessageStatus::Failed,
        ]);
    }
}
