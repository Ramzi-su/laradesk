<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(6), '.'),
            'description' => fake()->paragraphs(2, true),
            'status' => TicketStatus::Open,
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'category_id' => Category::factory(),
            'client_id' => User::factory()->client(),
            'agent_id' => null,
        ];
    }

    public function assignedTo(?User $agent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agent?->getKey() ?? User::factory()->agent(),
        ]);
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn (array $attributes) => ['priority' => $priority]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => ['status' => TicketStatus::InProgress]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Closed,
            'resolved_at' => now(),
            'closed_at' => now(),
        ]);
    }
}
