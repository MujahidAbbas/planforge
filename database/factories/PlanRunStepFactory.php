<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanRunStep>
 */
class PlanRunStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plan_run_id' => PlanRun::factory(),
            'step' => StepType::Tasks,
            'status' => PlanRunStepStatus::Queued,
            'attempt' => 1,
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'prompt_hash' => null,
            'request_meta' => null,
            'rate_limits' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
            'next_attempt_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStepStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStepStatus::Succeeded,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStepStatus::Failed,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
            'error_message' => 'Step execution failed.',
        ]);
    }

    public function delayed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlanRunStepStatus::Delayed,
            'started_at' => now()->subMinutes(2),
            'next_attempt_at' => now()->addMinutes(5),
        ]);
    }

    public function forStep(StepType $stepType): static
    {
        return $this->state(fn (array $attributes) => [
            'step' => $stepType,
        ]);
    }

    public function withRequestMeta(array $meta): static
    {
        return $this->state(fn (array $attributes) => [
            'request_meta' => $meta,
        ]);
    }

    public function withRateLimits(array $limits): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_limits' => $limits,
        ]);
    }
}
