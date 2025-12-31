<?php

namespace App\Actions;

use App\Enums\DocumentType;
use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use App\Jobs\GenerateTasksJob;
use App\Models\Document;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Project;
use App\Models\TaskSet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GenerateTasksFromTechSpec
{
    public function handle(Project $project, ?int $userId = null): TaskSet
    {
        return DB::transaction(function () use ($project, $userId) {
            $techDoc = Document::where('project_id', $project->id)
                ->where('type', DocumentType::Tech)
                ->with('currentVersion')
                ->first();

            if (! $techDoc?->currentVersion) {
                throw new RuntimeException('No Tech Spec found. Generate a Tech Spec first.');
            }

            $prdDoc = Document::where('project_id', $project->id)
                ->where('type', DocumentType::Prd)
                ->with('currentVersion')
                ->first();

            $run = PlanRun::create([
                'project_id' => $project->id,
                'triggered_by' => $userId,
                'status' => PlanRunStatus::Queued,
                'provider' => $project->preferred_provider ?? 'anthropic',
                'model' => $project->preferred_model ?? 'claude-sonnet-4-20250514',
                'input_snapshot' => [
                    'tech_version_id' => $techDoc->currentVersion->id,
                    'prd_version_id' => $prdDoc?->currentVersion?->id,
                ],
            ]);

            $step = PlanRunStep::create([
                'plan_run_id' => $run->id,
                'step' => StepType::Tasks,
                'status' => PlanRunStepStatus::Queued,
                'attempt' => 0,
                'provider' => $run->provider,
                'model' => $run->model,
            ]);

            $taskSet = TaskSet::create([
                'project_id' => $project->id,
                'source_tech_version_id' => $techDoc->currentVersion->id,
                'source_prd_version_id' => $prdDoc?->currentVersion?->id,
                'plan_run_id' => $run->id,
                'plan_run_step_id' => $step->id,
            ]);

            GenerateTasksJob::dispatch($run->id, $taskSet->id);

            return $taskSet;
        });
    }
}
