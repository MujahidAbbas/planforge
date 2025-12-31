<?php

namespace Database\Factories;

use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\TaskSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskSet>
 */
class TaskSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'source_tech_version_id' => DocumentVersion::factory(),
            'source_prd_version_id' => null,
            'plan_run_id' => null,
            'plan_run_step_id' => null,
            'meta' => null,
        ];
    }

    public function withPrdVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_prd_version_id' => DocumentVersion::factory(),
        ]);
    }

    public function withMeta(array $meta): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => $meta,
        ]);
    }
}
