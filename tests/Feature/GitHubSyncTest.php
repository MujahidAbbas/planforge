<?php

use App\Enums\IntegrationProvider;
use App\Enums\TaskStatus;
use App\Models\Integration;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\GitHub\GitHubApiService;
use App\Services\GitHub\GitHubSyncService;
use App\Services\GitHub\TaskToIssueMapper;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('GitHub Sync', function () {
    it('syncs a task to a new GitHub issue', function () {
        $this->mock(GitHubApiService::class, function ($mock) {
            $mock->shouldReceive('createIssue')
                ->once()
                ->andReturn([
                    'node_id' => 'I_abc123',
                    'number' => 42,
                    'html_url' => 'https://github.com/owner/repo/issues/42',
                    'state' => 'open',
                ]);
        });

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Todo,
        ]);

        $syncService = app(GitHubSyncService::class);
        $result = $syncService->syncTask($task, $integration, 'test-run-id');

        expect($result)
            ->action->toBe('created')
            ->issue_number->toBe(42);

        $this->assertDatabaseHas('external_links', [
            'task_id' => $task->id,
            'provider' => IntegrationProvider::GitHub->value,
            'external_number' => 42,
            'sync_status' => 'synced',
        ]);
    });

    it('updates an existing GitHub issue when task changes', function () {
        $this->mock(GitHubApiService::class, function ($mock) {
            $mock->shouldReceive('updateIssue')
                ->once()
                ->andReturn([
                    'node_id' => 'I_abc123',
                    'number' => 42,
                    'html_url' => 'https://github.com/owner/repo/issues/42',
                    'state' => 'open',
                ]);
        });

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Todo,
        ]);

        // Create existing external link with old hash
        $task->externalLinks()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_abc123',
            'external_number' => 42,
            'external_url' => 'https://github.com/owner/repo/issues/42',
            'external_state' => 'open',
            'sync_status' => 'synced',
            'last_synced_hash' => 'old-hash-that-no-longer-matches',
            'last_synced_at' => now(),
        ]);

        $syncService = app(GitHubSyncService::class);
        $result = $syncService->syncTask($task, $integration, 'test-run-id');

        expect($result)
            ->action->toBe('updated')
            ->issue_number->toBe(42);
    });

    it('skips syncing an unchanged task', function () {
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
        ]);

        // Create existing external link with matching hash
        $mapper = app(TaskToIssueMapper::class);
        $hash = $mapper->generateHash($task);

        $task->externalLinks()->create([
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_existing',
            'external_number' => 1,
            'external_url' => 'https://github.com/owner/repo/issues/1',
            'external_state' => 'open',
            'sync_status' => 'synced',
            'last_synced_hash' => $hash,
            'last_synced_at' => now(),
        ]);

        $syncService = app(GitHubSyncService::class);
        $result = $syncService->syncTask($task, $integration, 'test-run-id');

        expect($result)
            ->action->toBe('skipped')
            ->reason->toBe('unchanged');
    });

    it('records failure when API throws exception', function () {
        // Create the integration and task first
        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Todo,
        ]);

        // Mock the API service to throw an exception
        $this->mock(GitHubApiService::class, function ($mock) {
            $mock->shouldReceive('createIssue')
                ->once()
                ->andThrow(new \App\Exceptions\GitHubApiException('Rate limit exceeded', 403));
        });

        $syncService = app(GitHubSyncService::class);

        try {
            $syncService->syncTask($task, $integration, 'test-run-id');
        } catch (\App\Exceptions\GitHubApiException $e) {
            // Expected exception
        }

        $this->assertDatabaseHas('external_links', [
            'task_id' => $task->id,
            'sync_status' => 'failed',
        ]);
    });
});

describe('Task to Issue Mapper', function () {
    it('formats task title with category prefix', function () {
        $integration = Integration::factory()
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'title' => 'Implement backend feature',
            'category' => \App\Enums\TaskCategory::Backend,
        ]);

        $mapper = app(TaskToIssueMapper::class);
        $issue = $mapper->toIssue($task, $integration);

        expect($issue['title'])->toBe('[backend] Implement backend feature');
    });

    it('includes acceptance criteria as checklist', function () {
        $integration = Integration::factory()
            ->github()
            ->connected()
            ->withGitHubSettings()
            ->create();

        $task = Task::factory()->create([
            'title' => 'Test Task',
            'description' => 'Task description',
            'acceptance_criteria' => ['Criterion 1', 'Criterion 2'],
        ]);

        $mapper = app(TaskToIssueMapper::class);
        $issue = $mapper->toIssue($task, $integration);

        expect($issue['body'])
            ->toContain('## Acceptance Criteria')
            ->toContain('- [ ] Criterion 1')
            ->toContain('- [ ] Criterion 2');
    });

    it('generates consistent hash for change detection', function () {
        $task = Task::factory()->create([
            'title' => 'Test Task',
            'description' => 'Description',
        ]);

        $mapper = app(TaskToIssueMapper::class);
        $hash1 = $mapper->generateHash($task);
        $hash2 = $mapper->generateHash($task);

        expect($hash1)->toBe($hash2);

        // Modify task
        $task->title = 'Modified Title';
        $hash3 = $mapper->generateHash($task);

        expect($hash3)->not->toBe($hash1);
    });
});

describe('Integration Model', function () {
    it('can check if connected', function () {
        $connected = Integration::factory()
            ->connected()
            ->create();

        $pending = Integration::factory()
            ->create();

        expect($connected->isConnected())->toBeTrue();
        expect($pending->isConnected())->toBeFalse();
    });

    it('returns repo full name', function () {
        $integration = Integration::factory()
            ->withGitHubSettings('12345', 'acme', 'project')
            ->create();

        expect($integration->getRepoFullName())->toBe('acme/project');
    });

    it('returns installation id', function () {
        $integration = Integration::factory()
            ->withGitHubSettings('99999', 'owner', 'repo')
            ->create();

        expect($integration->getInstallationId())->toBe('99999');
    });
});
