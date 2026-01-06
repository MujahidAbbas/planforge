<?php

namespace App\Jobs;

use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSyncRunCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $syncRunId
    ) {}

    public function handle(): void
    {
        $syncRun = SyncRun::find($this->syncRunId);

        if (! $syncRun || $syncRun->status !== SyncRunStatus::Running) {
            return;
        }

        $processed = $syncRun->created_count +
                     $syncRun->updated_count +
                     $syncRun->skipped_count +
                     $syncRun->failed_count;

        if ($processed >= $syncRun->total_count) {
            $status = $syncRun->failed_count > 0
                ? SyncRunStatus::Partial
                : SyncRunStatus::Completed;

            $syncRun->update([
                'status' => $status,
                'completed_at' => now(),
            ]);
        } else {
            // Still processing, check again in 30 seconds
            self::dispatch($this->syncRunId)
                ->delay(now()->addSeconds(30));
        }
    }
}
