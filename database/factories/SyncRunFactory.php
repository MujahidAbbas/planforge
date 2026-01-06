<?php

namespace Database\Factories;

use App\Enums\SyncRunStatus;
use App\Models\Integration;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    protected $model = SyncRun::class;

    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'user_id' => User::factory(),
            'direction' => 'push',
            'trigger' => 'manual',
            'status' => SyncRunStatus::Running,
            'total_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SyncRunStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SyncRunStatus::Failed,
            'error_message' => 'Sync failed',
            'completed_at' => now(),
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SyncRunStatus::Partial,
            'failed_count' => 1,
            'completed_at' => now(),
        ]);
    }
}
