<?php

use App\Actions\GenerateTasksFromTechSpec;
use App\Enums\DocumentType;
use App\Enums\PlanRunStepStatus;
use App\Jobs\GenerateTasksJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\TaskSet;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

describe('GenerateTasksFromTechSpec action', function () {
    it('generates task set tied to tech version', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Create tech spec document with version
        $techDoc = Document::factory()->tech()->for($project)->create();
        $techVersion = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->withContent('# Tech Spec\n\n## API Endpoints\n\nPOST /projects')
            ->create();
        $techDoc->update(['current_version_id' => $techVersion->id]);

        $action = new GenerateTasksFromTechSpec;
        $taskSet = $action->handle($project);

        expect($taskSet)->toBeInstanceOf(TaskSet::class);
        expect($taskSet->source_tech_version_id)->toBe($techVersion->id);
        expect($taskSet->project_id)->toBe($project->id);
        expect($taskSet->status)->toBe(PlanRunStepStatus::Queued);

        Queue::assertPushed(GenerateTasksJob::class, function ($job) use ($taskSet) {
            return $job->taskSetId === $taskSet->id;
        });
    });

    it('throws exception when no tech spec exists', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $action = new GenerateTasksFromTechSpec;
        $action->handle($project);
    })->throws(RuntimeException::class, 'No Tech Spec found');

    it('throws exception when tech spec has no current version', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Create tech doc but no version
        Document::factory()->tech()->for($project)->create();

        $action = new GenerateTasksFromTechSpec;
        $action->handle($project);
    })->throws(RuntimeException::class, 'No Tech Spec found');

    it('includes prd version when available', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Create PRD
        $prdDoc = Document::factory()->prd()->for($project)->create();
        $prdVersion = DocumentVersion::factory()
            ->for($prdDoc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $prdDoc->update(['current_version_id' => $prdVersion->id]);

        // Create Tech Spec
        $techDoc = Document::factory()->tech()->for($project)->create();
        $techVersion = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->withContent('# Tech Spec')
            ->create();
        $techDoc->update(['current_version_id' => $techVersion->id]);

        $action = new GenerateTasksFromTechSpec;
        $taskSet = $action->handle($project);

        expect($taskSet->source_tech_version_id)->toBe($techVersion->id);
        expect($taskSet->source_prd_version_id)->toBe($prdVersion->id);
    });

    it('creates plan run and step for tracking', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techDoc = Document::factory()->tech()->for($project)->create();
        $techVersion = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->create();
        $techDoc->update(['current_version_id' => $techVersion->id]);

        $action = new GenerateTasksFromTechSpec;
        $taskSet = $action->handle($project);

        expect($taskSet->plan_run_id)->not->toBeNull();
        expect($taskSet->plan_run_step_id)->not->toBeNull();

        $this->assertDatabaseHas('plan_runs', [
            'id' => $taskSet->plan_run_id,
            'project_id' => $project->id,
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('plan_run_steps', [
            'id' => $taskSet->plan_run_step_id,
            'plan_run_id' => $taskSet->plan_run_id,
            'step' => 'tasks',
            'status' => 'queued',
        ]);
    });
});

describe('TaskSet stale detection', function () {
    it('returns false when task set matches current tech version', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techDoc = Document::factory()->tech()->for($project)->create();
        $techVersion = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->withContent('# Tech Spec v1')
            ->create();
        $techDoc->update(['current_version_id' => $techVersion->id]);

        $taskSet = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $techVersion->id,
        ]);

        expect($taskSet->isStale())->toBeFalse();
    });

    it('returns true when tech spec has been updated', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techDoc = Document::factory()->tech()->for($project)->create();

        // Create v1
        $v1 = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->withContent('# Tech Spec v1')
            ->create();
        $techDoc->update(['current_version_id' => $v1->id]);

        // Create task set from v1
        $taskSet = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $v1->id,
        ]);

        expect($taskSet->isStale())->toBeFalse();

        // Create v2 and update current
        $v2 = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->withContent('# Tech Spec v2 - Updated')
            ->create();
        $techDoc->update(['current_version_id' => $v2->id]);

        // Refresh and check stale
        expect($taskSet->fresh()->isStale())->toBeTrue();
    });

    it('returns false when no tech document exists', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        // Create task set without actual tech doc
        $orphanVersion = DocumentVersion::factory()->create();
        $taskSet = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $orphanVersion->id,
        ]);

        expect($taskSet->isStale())->toBeFalse();
    });
});

describe('TaskSet status from step', function () {
    it('returns status from associated plan run step', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techDoc = Document::factory()->tech()->for($project)->create();
        $techVersion = DocumentVersion::factory()
            ->for($techDoc, 'document')
            ->create();
        $techDoc->update(['current_version_id' => $techVersion->id]);

        $action = new GenerateTasksFromTechSpec;
        $taskSet = $action->handle($project);

        expect($taskSet->status)->toBe(PlanRunStepStatus::Queued);

        // Simulate step status change
        $taskSet->planRunStep->update(['status' => PlanRunStepStatus::Running]);
        expect($taskSet->fresh()->status)->toBe(PlanRunStepStatus::Running);

        $taskSet->planRunStep->update(['status' => PlanRunStepStatus::Succeeded]);
        expect($taskSet->fresh()->status)->toBe(PlanRunStepStatus::Succeeded);
    });

    it('returns null when no step associated', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techVersion = DocumentVersion::factory()->create();
        $taskSet = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $techVersion->id,
            'plan_run_step_id' => null,
        ]);

        expect($taskSet->status)->toBeNull();
    });
});

describe('Project task set relationships', function () {
    it('can access task sets from project', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techVersion = DocumentVersion::factory()->create();

        TaskSet::factory()->count(3)->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $techVersion->id,
        ]);

        expect($project->taskSets)->toHaveCount(3);
    });

    it('can get latest task set from project', function () {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $techVersion = DocumentVersion::factory()->create();

        $older = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $techVersion->id,
            'created_at' => now()->subDay(),
        ]);

        $newer = TaskSet::factory()->create([
            'project_id' => $project->id,
            'source_tech_version_id' => $techVersion->id,
            'created_at' => now(),
        ]);

        $latest = $project->latestTaskSet();

        expect($latest->id)->toBe($newer->id);
    });
});
