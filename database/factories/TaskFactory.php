<?php

namespace Database\Factories;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Relaticle\Flowforge\Services\DecimalPosition;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'epic_id' => null,
            'story_id' => null,
            'plan_run_id' => null,
            'plan_run_step_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'acceptance_criteria' => [
                'Given X, when Y, then Z',
                'Given A, when B, then C',
            ],
            'estimate' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'labels' => ['backend', 'priority:high'],
            'depends_on' => [],
            'status' => TaskStatus::Todo,
            'position' => DecimalPosition::forEmptyColumn(),
        ];
    }

    public function todo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Todo,
        ]);
    }

    public function doing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Doing,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
        ]);
    }

    public function withPosition(string $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    public function withCategory(TaskCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    public function withPriority(TaskPriority $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}
