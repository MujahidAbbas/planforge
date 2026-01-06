<?php

use App\Enums\IntegrationStatus;
use App\Enums\TaskStatus;
use App\Events\TasksChanged;
use App\Jobs\DebouncedGitHubSync;
use App\Listeners\QueueGitHubSync;
use App\Models\Integration;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('TasksChanged Event Dispatching', function () {
    it('dispatches event when task title is updated', function () {
        Event::fake([TasksChanged::class]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Original Title',
        ]);

        $task->update(['title' => 'Updated Title']);

        Event::assertDispatched(TasksChanged::class, function ($event) {
            return $event->project->id === $this->project->id
                && $event->changeType === 'updated';
        });
    });

    it('dispatches event when task status changes', function () {
        Event::fake([TasksChanged::class]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::Todo,
        ]);

        $task->update(['status' => TaskStatus::Done]);

        Event::assertDispatched(TasksChanged::class);
    });

    it('does not dispatch event for non-sync fields', function () {
        Event::fake([TasksChanged::class]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'position' => 1,
        ]);

        // Clear any events from creation
        Event::assertDispatched(TasksChanged::class);

        // Reset fake
        Event::fake([TasksChanged::class]);

        $task->update(['position' => 2]);

        Event::assertNotDispatched(TasksChanged::class);
    });

    it('dispatches event with created type for new tasks', function () {
        Event::fake([TasksChanged::class]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'New Task',
        ]);

        Event::assertDispatched(TasksChanged::class, function ($event) {
            return $event->changeType === 'created';
        });
    });

    it('dispatches event when task is deleted', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Event::fake([TasksChanged::class]);

        $task->delete();

        Event::assertDispatched(TasksChanged::class, function ($event) {
            return $event->changeType === 'deleted';
        });
    });
});

describe('QueueGitHubSync Listener', function () {
    it('queues debounced sync job for connected integration', function () {
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $event = new TasksChanged($this->project, 'updated');
        $listener = new QueueGitHubSync;
        $listener->handle($event);

        Queue::assertPushed(DebouncedGitHubSync::class, function ($job) {
            return $job->projectId === $this->project->id;
        });
    });

    it('does not queue job when no integration exists', function () {
        $event = new TasksChanged($this->project, 'updated');
        $listener = new QueueGitHubSync;
        $listener->handle($event);

        Queue::assertNotPushed(DebouncedGitHubSync::class);
    });

    it('does not queue job when integration is disabled', function () {
        Integration::factory()
            ->for($this->project)
            ->github()
            ->create(['status' => IntegrationStatus::Disabled]);

        $event = new TasksChanged($this->project, 'updated');
        $listener = new QueueGitHubSync;
        $listener->handle($event);

        Queue::assertNotPushed(DebouncedGitHubSync::class);
    });

    it('stores sync id in cache for debouncing', function () {
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $event = new TasksChanged($this->project, 'updated');
        $listener = new QueueGitHubSync;
        $listener->handle($event);

        $cacheKey = "github_sync_pending_{$this->project->id}";
        expect(Cache::has($cacheKey))->toBeTrue();
    });
});

describe('DebouncedGitHubSync Job', function () {
    it('skips when newer sync id exists', function () {
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $cacheKey = "github_sync_pending_{$this->project->id}";

        // Put a "newer" sync id in cache
        Cache::put($cacheKey, 'newer_sync_id', now()->addMinutes(5));

        $job = new DebouncedGitHubSync(
            $this->project->id,
            $integration->id,
            'older_sync_id'
        );

        // Run without Queue fake to actually execute
        Queue::fake();
        $job->handle(app(\App\Services\GitHub\GitHubSyncService::class));

        // Should not have dispatched any sync jobs
        Queue::assertNotPushed(\App\Jobs\SyncTaskToGitHub::class);
    });

    it('clears cache when executing sync', function () {
        Queue::fake();

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $syncId = 'test_sync_id';
        $cacheKey = "github_sync_pending_{$this->project->id}";

        Cache::put($cacheKey, $syncId, now()->addMinutes(5));

        $job = new DebouncedGitHubSync(
            $this->project->id,
            $integration->id,
            $syncId
        );

        $job->handle(app(\App\Services\GitHub\GitHubSyncService::class));

        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('Manual Sync Endpoint', function () {
    it('triggers sync for authenticated user', function () {
        Queue::fake();

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        // Create some tasks
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::Todo,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('integrations.github.sync', $this->project));

        $response->assertSuccessful();
        $response->assertJsonStructure(['message', 'sync_run_id', 'total_tasks']);

        $this->assertDatabaseHas('sync_runs', [
            'integration_id' => $integration->id,
            'user_id' => $this->user->id,
            'trigger' => 'manual',
        ]);
    });

    it('returns error when not connected', function () {
        $response = $this->actingAs($this->user)
            ->postJson(route('integrations.github.sync', $this->project));

        $response->assertStatus(400);
        $response->assertJson(['error' => 'GitHub integration not connected']);
    });

    it('returns sync status', function () {
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings('12345', 'owner', 'repo')
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('integrations.github.status', $this->project));

        $response->assertSuccessful();
        $response->assertJson([
            'connected' => true,
            'status' => 'connected',
            'repo' => 'owner/repo',
        ]);
    });
});
