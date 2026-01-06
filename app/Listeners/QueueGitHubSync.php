<?php

namespace App\Listeners;

use App\Enums\IntegrationStatus;
use App\Events\TasksChanged;
use App\Jobs\DebouncedGitHubSync;
use Illuminate\Support\Facades\Cache;

class QueueGitHubSync
{
    private const DEBOUNCE_SECONDS = 30;

    public function handle(TasksChanged $event): void
    {
        $project = $event->project;

        // Check if project has a connected GitHub integration
        $integration = $project->gitHubIntegration();

        if (! $integration || $integration->status !== IntegrationStatus::Connected) {
            return;
        }

        // Use cache to implement debounce
        $cacheKey = "github_sync_pending_{$project->id}";
        $syncId = uniqid('sync_', true);

        // Store the latest sync request ID
        Cache::put($cacheKey, $syncId, now()->addMinutes(5));

        // Dispatch a delayed job that will only run if this is still the latest request
        DebouncedGitHubSync::dispatch($project->id, $integration->id, $syncId)
            ->delay(now()->addSeconds(self::DEBOUNCE_SECONDS));
    }
}
