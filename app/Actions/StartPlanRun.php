<?php

namespace App\Actions;

use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Jobs\GeneratePrdJob;
use App\Jobs\GenerateTechSpecJob;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class StartPlanRun
{
    public function handle(Project $project, ?int $userId = null): PlanRun
    {
        return DB::transaction(function () use ($project, $userId) {
            $run = PlanRun::create([
                'project_id' => $project->id,
                'triggered_by' => $userId,
                'status' => PlanRunStatus::Queued,
                'provider' => $project->preferred_provider ?? 'anthropic',
                'model' => $project->preferred_model ?? 'claude-sonnet-4-20250514',
                'input_snapshot' => [
                    'idea' => $project->idea,
                    'constraints' => $project->constraints,
                ],
            ]);

            // Create steps up-front for UI progress tracking
            foreach ([StepType::Prd, StepType::Tech] as $step) {
                PlanRunStep::create([
                    'plan_run_id' => $run->id,
                    'step' => $step,
                    'status' => PlanRunStepStatus::Queued,
                    'attempt' => 0,
                    'provider' => $run->provider,
                    'model' => $run->model,
                ]);
            }

            // Job chaining - runs sequentially, stops if one fails
            // afterCommit ensures DB transaction is complete before workers grab jobs
            Bus::chain([
                new GeneratePrdJob($run->id),
                new GenerateTechSpecJob($run->id),
            ])
                ->catch(function (Throwable $e) use ($run) {
                    PlanRun::whereKey($run->id)->update([
                        'status' => PlanRunStatus::Failed,
                        'error_message' => $e->getMessage(),
                        'finished_at' => now(),
                    ]);
                })
                ->dispatch();

            return $run;
        });
    }
}
