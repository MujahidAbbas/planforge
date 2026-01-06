<?php

namespace App\Jobs;

use App\Enums\DocumentType;
use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Models\Document;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Task;
use App\Models\TaskSet;
use App\Schemas\TasksSchema;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Facades\Prism;
use Relaticle\Flowforge\Services\Rank;
use Throwable;

class GenerateTasksJob implements ShouldBeUnique, ShouldQueue
{
    use Concerns\ResolvesAiProvider;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public int $uniqueFor = 3600;

    public function __construct(
        public string $planRunId,
        public string $taskSetId
    ) {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->planRunId.':tasks';
    }

    public function middleware(): array
    {
        return [new RateLimited('llm:requests')];
    }

    public function handle(): void
    {
        $run = PlanRun::with('project')->findOrFail($this->planRunId);
        $taskSet = TaskSet::findOrFail($this->taskSetId);
        $step = PlanRunStep::where('plan_run_id', $run->id)
            ->where('step', StepType::Tasks)
            ->firstOrFail();

        $step->update([
            'status' => PlanRunStepStatus::Running,
            'attempt' => $step->attempt + 1,
            'started_at' => now(),
            'next_attempt_at' => null,
        ]);

        try {
            $techSpec = $taskSet->sourceTechVersion->content_md;

            $prdDoc = Document::where('project_id', $run->project_id)
                ->where('type', DocumentType::Prd)
                ->with('currentVersion')
                ->first();

            $prdSummary = $prdDoc?->currentVersion?->summary
                ?? $this->truncate($prdDoc?->currentVersion?->content_md, 2000);

            $response = $this->callAI($run, $techSpec, $prdSummary);

            Log::info('Task generation completed', [
                'project_id' => $run->project_id,
                'tasks_count' => count($response->structured['tasks'] ?? []),
            ]);

            $this->storeRateLimits($step, $response);
            $this->persistTasks($run->project_id, $taskSet, $response->structured);

            $step->update([
                'status' => PlanRunStepStatus::Succeeded,
                'finished_at' => now(),
            ]);

            $taskSet->update([
                'meta' => ['task_count' => count($response->structured['tasks'] ?? [])],
            ]);

            $run->update([
                'status' => PlanRunStatus::Succeeded,
                'finished_at' => now(),
            ]);

        } catch (PrismRateLimitedException $e) {
            $this->handleRateLimit($e, $step);
        } catch (Throwable $e) {
            $this->handleError($e, $step, $run);
        }
    }

    private function callAI(PlanRun $run, string $techSpec, ?string $prdSummary)
    {
        $provider = $this->resolveProvider($run->provider);

        $builder = Prism::structured()
            ->using($provider, $run->model)
            ->withSchema(TasksSchema::make())
            ->withMaxTokens(8000)
            ->withSystemPrompt(view('prompts.tasks.system')->render())
            ->withPrompt(view('prompts.tasks.user', [
                'project' => $run->project,
                'techSpec' => $techSpec,
                'prdSummary' => $prdSummary,
            ])->render())
            ->withClientOptions(['timeout' => 180]);

        if ($provider === Provider::OpenAI) {
            $builder = $builder->withProviderOptions(['schema' => ['strict' => true]]);
        }

        return $builder->asStructured();
    }

    private function persistTasks(string $projectId, TaskSet $taskSet, array $structured): void
    {
        DB::transaction(function () use ($projectId, $taskSet, $structured) {
            // Soft delete previous AI-generated tasks
            Task::where('project_id', $projectId)
                ->whereNotNull('task_set_id')
                ->delete();

            $tasks = $structured['tasks'] ?? [];
            $tempIdMap = [];

            // Generate lexicographic positions for proper Flowforge ordering
            $currentRank = Rank::forEmptySequence();

            foreach ($tasks as $data) {
                $task = Task::create([
                    'project_id' => $projectId,
                    'task_set_id' => $taskSet->id,
                    'plan_run_id' => $taskSet->plan_run_id,
                    'plan_run_step_id' => $taskSet->plan_run_step_id,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                    'category' => $data['category'] ?? null,
                    'priority' => $data['priority'] ?? 'med',
                    'status' => $data['status'] ?? 'todo',
                    'estimate' => $data['estimate'] ?? null,
                    'acceptance_criteria' => $data['acceptance_criteria'] ?? [],
                    'source_refs' => $data['source_refs'] ?? [],
                    'labels' => $data['labels'] ?? [],
                    'depends_on' => [],
                    'position' => $currentRank->get(),
                ]);

                // Generate next position
                $currentRank = Rank::after($currentRank);

                if (isset($data['temp_id'])) {
                    $tempIdMap[$data['temp_id']] = $task->id;
                }
            }

            // Resolve dependencies
            foreach ($tasks as $data) {
                if (empty($data['depends_on']) || ! isset($data['temp_id'])) {
                    continue;
                }

                $taskId = $tempIdMap[$data['temp_id']] ?? null;
                if (! $taskId) {
                    continue;
                }

                $resolvedDeps = collect($data['depends_on'])
                    ->map(fn ($tempId) => $tempIdMap[$tempId] ?? null)
                    ->filter()
                    ->values()
                    ->toArray();

                Task::where('id', $taskId)->update(['depends_on' => $resolvedDeps]);
            }
        });
    }

    private function storeRateLimits(PlanRunStep $step, $response): void
    {
        if (! $response->meta->rateLimits) {
            return;
        }

        $step->update([
            'rate_limits' => collect($response->meta->rateLimits)->map(fn ($rl) => [
                'name' => $rl->name,
                'limit' => $rl->limit,
                'remaining' => $rl->remaining,
                'resetsAt' => $rl->resetsAt?->toIso8601String(),
            ])->toArray(),
        ]);
    }

    private function handleRateLimit(PrismRateLimitedException $e, PlanRunStep $step): void
    {
        $resetAt = collect($e->rateLimits)
            ->map(fn ($rl) => $rl->resetsAt)
            ->filter()
            ->sort()
            ->first();

        $delaySeconds = $resetAt
            ? max(5, now()->diffInSeconds($resetAt, false) + 5)
            : 60;

        $step->update([
            'status' => PlanRunStepStatus::Delayed,
            'next_attempt_at' => now()->addSeconds($delaySeconds),
            'rate_limits' => collect($e->rateLimits)->map(fn ($rl) => [
                'name' => $rl->name,
                'limit' => $rl->limit,
                'remaining' => $rl->remaining,
                'resetsAt' => $rl->resetsAt?->toIso8601String(),
            ])->toArray(),
        ]);

        $this->release($delaySeconds);
    }

    private function handleError(Throwable $e, PlanRunStep $step, PlanRun $run): void
    {
        $isTransient = str_contains(strtolower($e->getMessage()), 'overload')
            || str_contains(strtolower($e->getMessage()), 'capacity')
            || str_contains(strtolower($e->getMessage()), 'temporarily');

        if ($isTransient && $this->attempts() < $this->tries) {
            $delaySeconds = $this->backoff[$this->attempts() - 1] ?? 300;

            $step->update([
                'status' => PlanRunStepStatus::Delayed,
                'next_attempt_at' => now()->addSeconds($delaySeconds),
                'error_message' => $e->getMessage(),
            ]);

            $this->release($delaySeconds);

            return;
        }

        $step->update([
            'status' => PlanRunStepStatus::Failed,
            'error_message' => $e->getMessage(),
            'finished_at' => now(),
        ]);

        $run->update([
            'status' => PlanRunStatus::Failed,
            'error_message' => $e->getMessage(),
            'finished_at' => now(),
        ]);

        throw $e;
    }

    private function truncate(?string $text, int $length): ?string
    {
        if ($text === null) {
            return null;
        }

        return strlen($text) > $length ? substr($text, 0, $length).'...' : $text;
    }
}
