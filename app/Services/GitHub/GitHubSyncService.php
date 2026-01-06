<?php

namespace App\Services\GitHub;

use App\Enums\SyncRunStatus;
use App\Enums\TaskStatus;
use App\Jobs\CheckSyncRunCompletion;
use App\Jobs\SyncTaskToGitHub;
use App\Models\Integration;
use App\Models\SyncRun;
use App\Models\Task;

class GitHubSyncService
{
    public function __construct(
        private GitHubApiService $api,
        private TaskToIssueMapper $mapper
    ) {}

    /**
     * Start a sync run for all tasks in a project.
     */
    public function syncProject(Integration $integration, ?int $userId = null): SyncRun
    {
        // Only sync tasks from the latest TaskSet
        $latestTaskSet = $integration->project->latestTaskSet();

        $tasksQuery = $integration->project->tasks()
            ->whereIn('status', [
                TaskStatus::Todo,
                TaskStatus::Doing,
                TaskStatus::Done,
            ]);

        // If there's a latest TaskSet, only sync those tasks
        if ($latestTaskSet) {
            $tasksQuery->where('task_set_id', $latestTaskSet->id);
        }

        $tasks = $tasksQuery->get();

        // Create sync run record
        $syncRun = SyncRun::create([
            'integration_id' => $integration->id,
            'user_id' => $userId,
            'direction' => 'push',
            'trigger' => $userId ? 'manual' : 'scheduled',
            'status' => SyncRunStatus::Running,
            'started_at' => now(),
            'total_count' => $tasks->count(),
        ]);

        // Dispatch individual sync jobs
        foreach ($tasks as $task) {
            SyncTaskToGitHub::dispatch($task, $integration, $syncRun->id);
        }

        // Dispatch completion checker (30 seconds should be enough for most syncs)
        CheckSyncRunCompletion::dispatch($syncRun->id)
            ->delay(now()->addSeconds(30));

        return $syncRun;
    }

    /**
     * Sync a single task to GitHub.
     *
     * @return array<string, mixed>
     */
    public function syncTask(Task $task, Integration $integration, string $syncRunId): array
    {
        $settings = $integration->settings;
        $installationId = $settings['installation_id'];
        $owner = $settings['owner'];
        $repo = $settings['repo'];

        // Check if task already has an external link
        $externalLink = $task->externalLinks()
            ->where('integration_id', $integration->id)
            ->first();

        // Generate current content hash
        $currentHash = $this->mapper->generateHash($task);

        // Skip if unchanged
        if ($externalLink && $externalLink->last_synced_hash === $currentHash) {
            return ['action' => 'skipped', 'reason' => 'unchanged'];
        }

        $issueData = $this->mapper->toIssue($task, $integration);

        try {
            if ($externalLink && $externalLink->external_number) {
                // Update existing issue
                $issue = $this->api->updateIssue(
                    $installationId,
                    $owner,
                    $repo,
                    $externalLink->external_number,
                    $issueData
                );
                $action = 'updated';
            } else {
                // Create new issue
                $issue = $this->api->createIssue(
                    $installationId,
                    $owner,
                    $repo,
                    $issueData
                );
                $action = 'created';
            }

            // Update or create external link
            $task->externalLinks()->updateOrCreate(
                ['integration_id' => $integration->id],
                [
                    'provider' => 'github',
                    'external_id' => $issue['node_id'],
                    'external_number' => $issue['number'],
                    'external_url' => $issue['html_url'],
                    'external_state' => $issue['state'],
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'last_synced_at' => now(),
                    'last_synced_hash' => $currentHash,
                ]
            );

            return ['action' => $action, 'issue_number' => $issue['number']];

        } catch (\Exception $e) {
            // Record failure
            if ($externalLink) {
                $externalLink->update([
                    'sync_status' => 'failed',
                    'sync_error' => $e->getMessage(),
                ]);
            } else {
                // Create a pending external link to track the failure
                $task->externalLinks()->create([
                    'integration_id' => $integration->id,
                    'provider' => 'github',
                    'external_id' => 'pending_'.uniqid(),
                    'sync_status' => 'failed',
                    'sync_error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
