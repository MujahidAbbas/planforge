<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Project;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class DebouncedGitHubSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $projectId,
        public string $integrationId,
        public string $syncId
    ) {}

    public function handle(GitHubSyncService $syncService): void
    {
        // Check if this is still the latest sync request (debounce check)
        $cacheKey = "github_sync_pending_{$this->projectId}";
        $latestSyncId = Cache::get($cacheKey);

        if ($latestSyncId !== $this->syncId) {
            // A newer sync was requested, skip this one
            logger()->debug('Skipping debounced GitHub sync - newer request exists', [
                'project_id' => $this->projectId,
                'sync_id' => $this->syncId,
                'latest_sync_id' => $latestSyncId,
            ]);

            return;
        }

        // Clear the pending sync marker
        Cache::forget($cacheKey);

        // Load the models
        $project = Project::find($this->projectId);
        $integration = Integration::find($this->integrationId);

        if (! $project || ! $integration) {
            logger()->warning('DebouncedGitHubSync: Project or integration not found', [
                'project_id' => $this->projectId,
                'integration_id' => $this->integrationId,
            ]);

            return;
        }

        // Verify integration is still connected
        if (! $integration->isConnected()) {
            logger()->debug('DebouncedGitHubSync: Integration not connected', [
                'integration_id' => $this->integrationId,
            ]);

            return;
        }

        // Execute the sync (no user_id for auto-triggered syncs)
        $syncRun = $syncService->syncProject($integration, null);

        logger()->info('Auto-sync triggered for GitHub', [
            'project_id' => $this->projectId,
            'sync_run_id' => $syncRun->id,
            'total_tasks' => $syncRun->total_count,
        ]);
    }
}
