<?php

namespace App\Jobs;

use App\Enums\DocumentType;
use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Facades\Prism;
use Throwable;

class GeneratePrdJob implements ShouldBeUnique, ShouldQueue
{
    use Concerns\ResolvesAiProvider;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 120, 300];

    public int $uniqueFor = 3600;

    public function __construct(public string $planRunId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->planRunId.':prd';
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('llm:requests')];
    }

    public function handle(): void
    {
        $run = PlanRun::query()->with('project')->findOrFail($this->planRunId);
        $step = PlanRunStep::query()
            ->where('plan_run_id', $run->id)
            ->where('step', StepType::Prd)
            ->firstOrFail();

        // Update run and step status
        $run->update([
            'status' => PlanRunStatus::Running,
            'started_at' => $run->started_at ?? now(),
        ]);
        $step->update([
            'status' => PlanRunStepStatus::Running,
            'attempt' => $step->attempt + 1,
            'started_at' => now(),
            'next_attempt_at' => null,
        ]);

        try {
            // Load template if set
            $template = $run->project->prdTemplate;

            $providerEnum = $this->resolveProvider($run->provider);
            $system = view('prompts.prd.system')->render();
            $prompt = view('prompts.prd.user', [
                'project' => $run->project,
                'template' => $template,
            ])->render();

            $response = Prism::text()
                ->using($providerEnum, $run->model)
                ->withMaxTokens(4000)
                ->withSystemPrompt($system)
                ->withPrompt($prompt)
                ->withClientOptions(['timeout' => 120])
                ->asText();

            // Store rate limit info from successful response
            if ($response->meta->rateLimits) {
                $step->update([
                    'rate_limits' => collect($response->meta->rateLimits)->map(fn ($rl) => [
                        'name' => $rl->name,
                        'limit' => $rl->limit,
                        'remaining' => $rl->remaining,
                        'resetsAt' => $rl->resetsAt?->toIso8601String(),
                    ])->toArray(),
                ]);
            }

            // Create or get document
            $doc = Document::firstOrCreate([
                'project_id' => $run->project_id,
                'type' => DocumentType::Prd,
            ]);

            // Create new version
            $version = DocumentVersion::create([
                'document_id' => $doc->id,
                'plan_run_id' => $run->id,
                'plan_run_step_id' => $step->id,
                'content_md' => $response->text,
            ]);

            // Point document to new version
            $doc->update(['current_version_id' => $version->id]);

            // Record template usage
            if ($template) {
                $template->recordUsage();
            }

            // Mark step as succeeded
            $step->update([
                'status' => PlanRunStepStatus::Succeeded,
                'finished_at' => now(),
            ]);

        } catch (PrismRateLimitedException $e) {
            // Find the soonest reset time
            $resetAt = collect($e->rateLimits)
                ->map(fn ($rl) => $rl->resetsAt)
                ->filter()
                ->sort()
                ->first();

            $delaySeconds = $resetAt
                ? max(5, now()->diffInSeconds($resetAt, false) + 5)
                : 60; // Default to 60s if no reset time provided

            // Update step to delayed status
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

            // Release job back to queue with delay
            $this->release($delaySeconds);

        } catch (Throwable $e) {
            // Check if it's a transient/overload error
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

            // Non-retryable or max attempts reached
            $step->update([
                'status' => PlanRunStepStatus::Failed,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}
