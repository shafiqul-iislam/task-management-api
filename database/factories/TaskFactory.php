<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => TaskStatusEnum::PENDING->value,
            'due_date'    => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TaskStatusEnum::PENDING->value]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatusEnum::IN_PROGRESS->value]);
    }

    public function completed(): static
    {
        return $this->state(['status' => TaskStatusEnum::COMPLETED->value]);
    }
}
