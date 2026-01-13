<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanRunStatus;
use App\Models\PlanRun;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanRun>
 */
class PlanRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'triggered_by' => User::factory(),
            'status' => PlanRunStatus::Queued,
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'input_snapshot' => [],
            'metrics' => [],
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStatus::Succeeded,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStatus::Failed,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'error_message' => 'An error occurred during processing.',
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStatus::Partial,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }

    public function withMetrics(array $metrics): static
    {
        return $this->state(fn (array $attributes) => [
            'metrics' => $metrics,
        ]);
    }

    public function withInputSnapshot(array $snapshot): static
    {
        return $this->state(fn (array $attributes) => [
            'input_snapshot' => $snapshot,
        ]);
    }
}
