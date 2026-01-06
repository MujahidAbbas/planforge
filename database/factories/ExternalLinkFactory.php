<?php

namespace Database\Factories;

use App\Enums\ExternalLinkSyncStatus;
use App\Enums\IntegrationProvider;
use App\Models\ExternalLink;
use App\Models\Integration;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalLink>
 */
class ExternalLinkFactory extends Factory
{
    protected $model = ExternalLink::class;

    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'task_id' => Task::factory(),
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_'.$this->faker->unique()->regexify('[a-zA-Z0-9]{10}'),
            'external_number' => $this->faker->unique()->numberBetween(1, 10000),
            'external_url' => $this->faker->url(),
            'external_state' => 'open',
            'sync_status' => ExternalLinkSyncStatus::Synced,
            'sync_error' => null,
            'last_synced_at' => now(),
            'last_synced_hash' => hash('sha256', $this->faker->sentence()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => ExternalLinkSyncStatus::Pending,
            'last_synced_at' => null,
            'last_synced_hash' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => ExternalLinkSyncStatus::Failed,
            'sync_error' => 'API request failed',
        ]);
    }

    public function orphaned(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => ExternalLinkSyncStatus::Orphaned,
            'external_state' => 'deleted',
            'sync_error' => 'Issue was deleted in GitHub',
        ]);
    }
}
