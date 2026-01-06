<?php

namespace App\Livewire\Projects\Tabs;

use App\Models\Integration;
use App\Models\Project;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Integrations extends Component
{
    public string $projectId;

    public bool $isSyncing = false;

    public ?string $syncMessage = null;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[Computed]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }

    #[Computed]
    public function githubIntegration(): ?Integration
    {
        return $this->project->gitHubIntegration();
    }

    #[Computed]
    public function recentSyncRuns(): array
    {
        $integration = $this->githubIntegration;

        if (! $integration) {
            return [];
        }

        return $integration->syncRuns()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($run) => [
                'id' => $run->id,
                'status' => $run->status->value,
                'trigger' => $run->trigger,
                'total' => $run->total_count,
                'created' => $run->created_count,
                'updated' => $run->updated_count,
                'skipped' => $run->skipped_count,
                'failed' => $run->failed_count,
                'started_at' => $run->started_at->diffForHumans(),
                'completed_at' => $run->completed_at?->diffForHumans(),
            ])
            ->toArray();
    }

    #[Computed]
    public function lastSyncedAt(): ?string
    {
        $integration = $this->githubIntegration;

        if (! $integration) {
            return null;
        }

        $lastCompletedSync = $integration->syncRuns()
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        return $lastCompletedSync?->completed_at->diffForHumans();
    }

    #[Computed]
    public function hasRunningSyncs(): bool
    {
        $integration = $this->githubIntegration;

        if (! $integration) {
            return false;
        }

        return $integration->syncRuns()
            ->where('status', \App\Enums\SyncRunStatus::Running)
            ->exists();
    }

    #[On('triggerSync')]
    public function triggerSync(): void
    {
        $integration = $this->githubIntegration;

        if (! $integration || ! $integration->isConnected()) {
            $this->syncMessage = 'GitHub integration is not connected.';

            return;
        }

        $this->isSyncing = true;

        try {
            $syncService = app(GitHubSyncService::class);
            $syncRun = $syncService->syncProject($integration, Auth::id());

            $this->syncMessage = "Syncing {$syncRun->total_count} tasks to GitHub...";
        } catch (\Exception $e) {
            $this->syncMessage = 'Sync failed: '.$e->getMessage();

            Log::error('GitHub sync failed', [
                'error' => $e->getMessage(),
                'project_id' => $this->projectId,
            ]);
        }

        $this->isSyncing = false;
    }

    public function render()
    {
        return view('livewire.projects.tabs.integrations');
    }
}
