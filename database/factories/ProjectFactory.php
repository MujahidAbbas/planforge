<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'idea' => fake()->paragraph(),
            'constraints' => [
                'Must be mobile responsive',
                'Use existing tech stack',
            ],
            'preferred_provider' => 'anthropic',
            'preferred_model' => 'claude-sonnet-4-20250514',
            'status' => ProjectStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Archived,
        ]);
    }
}
