<?php

namespace App\Actions;

use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Jobs\GenerateTechSpecJob;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Project;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegenerateTech
{
    /**
     * Regenerate Tech Spec using the current active PRD.
     */
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
                    'regenerate' => 'tech',
                ],
            ]);

            PlanRunStep::create([
                'plan_run_id' => $run->id,
                'step' => StepType::Tech,
                'status' => PlanRunStepStatus::Queued,
                'attempt' => 0,
                'provider' => $run->provider,
                'model' => $run->model,
            ]);

            Bus::chain([
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
