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

class RegeneratePrd
{
    /**
     * Regenerate PRD, optionally including downstream artifacts.
     *
     * @param  bool  $includeDownstream  If true, also regenerates Tech (and Tasks when implemented)
     */
    public function handle(Project $project, ?int $userId = null, bool $includeDownstream = false): PlanRun
    {
        return DB::transaction(function () use ($project, $userId, $includeDownstream) {
            $run = PlanRun::create([
                'project_id' => $project->id,
                'triggered_by' => $userId,
                'status' => PlanRunStatus::Queued,
                'provider' => $project->preferred_provider ?? 'anthropic',
                'model' => $project->preferred_model ?? 'claude-sonnet-4-20250514',
                'input_snapshot' => [
                    'idea' => $project->idea,
                    'constraints' => $project->constraints,
                    'regenerate' => 'prd',
                    'include_downstream' => $includeDownstream,
                ],
            ]);

            // Determine which steps to create
            $steps = [StepType::Prd];
            if ($includeDownstream) {
                $steps[] = StepType::Tech;
                // $steps[] = StepType::Tasks; // When implemented
            }

            foreach ($steps as $step) {
                PlanRunStep::create([
                    'plan_run_id' => $run->id,
                    'step' => $step,
                    'status' => PlanRunStepStatus::Queued,
                    'attempt' => 0,
                    'provider' => $run->provider,
                    'model' => $run->model,
                ]);
            }

            // Build job chain
            $jobs = [new GeneratePrdJob($run->id)];
            if ($includeDownstream) {
                $jobs[] = new GenerateTechSpecJob($run->id);
                // $jobs[] = new GenerateTasksJob($run->id); // When implemented
            }

            Bus::chain($jobs)
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
