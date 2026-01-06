<?php

use App\Enums\IntegrationProvider;
use App\Enums\TaskStatus;
use App\Models\ExternalLink;
use App\Models\Integration;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('Webhook Signature Verification', function () {
    it('accepts requests without signature when secret is not configured', function () {
        config(['services.github.webhook_secret' => null]);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'ping',
        ], [
            'X-GitHub-Event' => 'ping',
        ]);

        $response->assertSuccessful();
    });

    it('rejects requests with invalid signature when secret is configured', function () {
        config(['services.github.webhook_secret' => 'test-secret']);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'ping',
        ], [
            'X-GitHub-Event' => 'ping',
            'X-Hub-Signature-256' => 'sha256=invalid',
        ]);

        $response->assertStatus(401);
    });

    it('accepts requests with valid signature', function () {
        $secret = 'test-secret';
        config(['services.github.webhook_secret' => $secret]);

        $payload = json_encode(['action' => 'ping', 'zen' => 'Test']);
        $signature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        $response = $this->call(
            'POST',
            route('webhooks.github'),
            [],
            [],
            [],
            [
                'HTTP_X-GitHub-Event' => 'ping',
                'HTTP_X-Hub-Signature-256' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertSuccessful();
    });
});

describe('Ping Event', function () {
    it('responds to ping with pong', function () {
        config(['services.github.webhook_secret' => null]);

        $response = $this->postJson(route('webhooks.github'), [
            'zen' => 'Non-blocking is better than blocking.',
            'hook_id' => 12345,
        ], [
            'X-GitHub-Event' => 'ping',
        ]);

        $response->assertSuccessful();
        $response->assertJson(['message' => 'pong']);
    });
});

describe('Issue Closed Event', function () {
    it('updates task status to done when issue is closed', function () {
        config(['services.github.webhook_secret' => null]);

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings('12345', 'owner', 'repo')
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Doing,
        ]);

        ExternalLink::factory()->create([
            'task_id' => $task->id,
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_node123',
            'external_number' => 42,
            'external_url' => 'https://github.com/owner/repo/issues/42',
            'external_state' => 'open',
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'closed',
            'issue' => [
                'node_id' => 'I_node123',
                'number' => 42,
                'state' => 'closed',
                'title' => 'Test Task',
            ],
            'repository' => [
                'full_name' => 'owner/repo',
            ],
        ], [
            'X-GitHub-Event' => 'issues',
        ]);

        $response->assertSuccessful();
        $response->assertJson(['action' => 'closed']);

        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Done);
    });
});

describe('Issue Reopened Event', function () {
    it('updates task status when issue is reopened', function () {
        config(['services.github.webhook_secret' => null]);

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings('12345', 'owner', 'repo')
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Done,
        ]);

        ExternalLink::factory()->create([
            'task_id' => $task->id,
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_node456',
            'external_number' => 99,
            'external_url' => 'https://github.com/owner/repo/issues/99',
            'external_state' => 'closed',
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'reopened',
            'issue' => [
                'node_id' => 'I_node456',
                'number' => 99,
                'state' => 'open',
                'title' => 'Test Task',
            ],
            'repository' => [
                'full_name' => 'owner/repo',
            ],
        ], [
            'X-GitHub-Event' => 'issues',
        ]);

        $response->assertSuccessful();
        $response->assertJson(['action' => 'reopened']);

        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Doing);
    });

    it('respects sync_reopened_as setting', function () {
        config(['services.github.webhook_secret' => null]);

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->create([
                'settings' => [
                    'installation_id' => '12345',
                    'owner' => 'owner',
                    'repo' => 'repo',
                    'sync_reopened_as' => 'todo',
                ],
            ]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::Done,
        ]);

        ExternalLink::factory()->create([
            'task_id' => $task->id,
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_node789',
            'external_number' => 100,
            'external_state' => 'closed',
        ]);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'reopened',
            'issue' => [
                'node_id' => 'I_node789',
                'number' => 100,
                'state' => 'open',
            ],
            'repository' => [
                'full_name' => 'owner/repo',
            ],
        ], [
            'X-GitHub-Event' => 'issues',
        ]);

        $response->assertSuccessful();

        $task->refresh();
        expect($task->status)->toBe(TaskStatus::Todo);
    });
});

describe('Untracked Issues', function () {
    it('ignores issues not tracked in the system', function () {
        config(['services.github.webhook_secret' => null]);

        $response = $this->postJson(route('webhooks.github'), [
            'action' => 'closed',
            'issue' => [
                'node_id' => 'I_unknown',
                'number' => 9999,
                'state' => 'closed',
            ],
            'repository' => [
                'full_name' => 'unknown/repo',
            ],
        ], [
            'X-GitHub-Event' => 'issues',
        ]);

        $response->assertSuccessful();
        $response->assertJson(['message' => 'Issue not tracked']);
    });
});

describe('External Link State Update', function () {
    it('updates external link state on issue events', function () {
        config(['services.github.webhook_secret' => null]);

        $integration = Integration::factory()
            ->for($this->project)
            ->github()
            ->connected()
            ->withGitHubSettings('12345', 'owner', 'repo')
            ->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::Doing,
        ]);

        $externalLink = ExternalLink::factory()->create([
            'task_id' => $task->id,
            'integration_id' => $integration->id,
            'provider' => IntegrationProvider::GitHub,
            'external_id' => 'I_statetest',
            'external_number' => 50,
            'external_state' => 'open',
        ]);

        $this->postJson(route('webhooks.github'), [
            'action' => 'closed',
            'issue' => [
                'node_id' => 'I_statetest',
                'number' => 50,
                'state' => 'closed',
            ],
            'repository' => [
                'full_name' => 'owner/repo',
            ],
        ], [
            'X-GitHub-Event' => 'issues',
        ]);

        $externalLink->refresh();
        expect($externalLink->external_state)->toBe('closed');
    });
});
