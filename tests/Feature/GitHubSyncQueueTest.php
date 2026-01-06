<?php

declare(strict_types=1);

use App\Enums\IntegrationStatus;
use App\Enums\SyncRunStatus;
use App\Enums\TaskStatus;
use App\Jobs\CheckSyncRunCompletion;
use App\Jobs\SyncTaskToGitHub;
use App\Models\Integration;
use App\Models\Project;
use App\Models\SyncRun;
use App\Models\Task;
use App\Models\TaskSet;
use App\Models\User;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Support\Facades\Queue;

test('sync dispatches jobs to queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $taskSet = TaskSet::factory()->create(['project_id' => $project->id]);

    $tasks = Task::factory()->count(3)->create([
        'project_id' => $project->id,
        'task_set_id' => $taskSet->id,
        'status' => TaskStatus::Todo,
    ]);

    $integration = Integration::factory()->create([
        'project_id' => $project->id,
        'provider' => 'github',
        'status' => IntegrationStatus::Connected,
        'settings' => [
            'installation_id' => '12345',
            'owner' => 'test-owner',
            'repo' => 'test-repo',
        ],
    ]);

    $syncService = app(GitHubSyncService::class);
    $syncRun = $syncService->syncProject($integration, $user->id);

    // Assert jobs were dispatched
    Queue::assertPushed(SyncTaskToGitHub::class, 3);
    Queue::assertPushed(CheckSyncRunCompletion::class, 1);

    // Assert sync run was created
    expect($syncRun)->toBeInstanceOf(SyncRun::class);
    expect($syncRun->status)->toBe(SyncRunStatus::Running);
    expect($syncRun->total_count)->toBe(3);
});

test('check sync run completion marks sync as completed', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $integration = Integration::factory()->create([
        'project_id' => $project->id,
        'provider' => 'github',
        'status' => IntegrationStatus::Connected,
    ]);

    $syncRun = SyncRun::create([
        'integration_id' => $integration->id,
        'user_id' => $user->id,
        'direction' => 'push',
        'trigger' => 'manual',
        'status' => SyncRunStatus::Running,
        'started_at' => now(),
        'total_count' => 10,
        'created_count' => 3,
        'updated_count' => 2,
        'skipped_count' => 5,
        'failed_count' => 0,
    ]);

    $job = new CheckSyncRunCompletion($syncRun->id);
    $job->handle();

    $syncRun->refresh();

    expect($syncRun->status)->toBe(SyncRunStatus::Completed);
    expect($syncRun->completed_at)->not->toBeNull();
});

test('check sync run completion marks sync as partial when there are failures', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $integration = Integration::factory()->create([
        'project_id' => $project->id,
        'provider' => 'github',
        'status' => IntegrationStatus::Connected,
    ]);

    $syncRun = SyncRun::create([
        'integration_id' => $integration->id,
        'user_id' => $user->id,
        'direction' => 'push',
        'trigger' => 'manual',
        'status' => SyncRunStatus::Running,
        'started_at' => now(),
        'total_count' => 10,
        'created_count' => 3,
        'updated_count' => 2,
        'skipped_count' => 3,
        'failed_count' => 2,
    ]);

    $job = new CheckSyncRunCompletion($syncRun->id);
    $job->handle();

    $syncRun->refresh();

    expect($syncRun->status)->toBe(SyncRunStatus::Partial);
    expect($syncRun->completed_at)->not->toBeNull();
});

test('check sync run completion reschedules if not all tasks processed', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    $integration = Integration::factory()->create([
        'project_id' => $project->id,
        'provider' => 'github',
        'status' => IntegrationStatus::Connected,
    ]);

    $syncRun = SyncRun::create([
        'integration_id' => $integration->id,
        'user_id' => $user->id,
        'direction' => 'push',
        'trigger' => 'manual',
        'status' => SyncRunStatus::Running,
        'started_at' => now(),
        'total_count' => 10,
        'created_count' => 2,
        'updated_count' => 1,
        'skipped_count' => 2,
        'failed_count' => 0,
    ]);

    $job = new CheckSyncRunCompletion($syncRun->id);
    $job->handle();

    $syncRun->refresh();

    // Should still be running
    expect($syncRun->status)->toBe(SyncRunStatus::Running);
    expect($syncRun->completed_at)->toBeNull();

    // Should reschedule itself
    Queue::assertPushed(CheckSyncRunCompletion::class, 1);
});
