<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\SyncRun;
use App\Models\Task;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncTaskToGitHub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public Task $task,
        public Integration $integration,
        public string $syncRunId
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // Prevent overlapping syncs for the same task
        return [
            (new WithoutOverlapping($this->task->id))->dontRelease(),
        ];
    }

    public function handle(GitHubSyncService $syncService): void
    {
        $result = $syncService->syncTask(
            $this->task,
            $this->integration,
            $this->syncRunId
        );

        // Update sync run stats
        $this->updateSyncRunStats($result['action']);

        // Add delay between requests to respect rate limits
        usleep(500000); // 0.5 second delay
    }

    public function failed(\Throwable $exception): void
    {
        $this->updateSyncRunStats('failed');

        logger()->error('Task sync to GitHub failed', [
            'task_id' => $this->task->id,
            'integration_id' => $this->integration->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function updateSyncRunStats(string $action): void
    {
        $syncRun = SyncRun::find($this->syncRunId);
        if ($syncRun) {
            $syncRun->incrementStat($action);
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }
}
