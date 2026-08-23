<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'status' => Status::Open,
            'priority' => Priority::Normal,
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'team_id' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Open,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Pending,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Status::Closed,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => Priority::Urgent,
        ]);
    }
}
