<?php

namespace Database\Factories;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\Integration;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'provider' => IntegrationProvider::GitHub,
            'status' => IntegrationStatus::Pending,
            'settings' => [],
            'credentials' => null,
            'error_message' => null,
            'last_synced_at' => null,
        ];
    }

    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => IntegrationProvider::GitHub,
        ]);
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IntegrationStatus::Connected,
        ]);
    }

    public function withGitHubSettings(
        string $installationId = '12345',
        string $owner = 'test-owner',
        string $repo = 'test-repo'
    ): static {
        return $this->state(fn (array $attributes) => [
            'settings' => [
                'installation_id' => $installationId,
                'owner' => $owner,
                'repo' => $repo,
                'default_labels' => ['planforge'],
                'sync_closed_as' => 'done',
                'sync_reopened_as' => 'doing',
            ],
        ]);
    }
}
