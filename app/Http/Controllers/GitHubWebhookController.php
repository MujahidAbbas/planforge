<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Enums\TaskStatus;
use App\Models\ExternalLink;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    /**
     * Handle incoming GitHub webhooks.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('GitHub webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->header('X-GitHub-Event');
        $payload = $request->all();

        Log::info('GitHub webhook received', [
            'event' => $event,
            'action' => $payload['action'] ?? null,
        ]);

        return match ($event) {
            'issues' => $this->handleIssueEvent($payload),
            'ping' => $this->handlePing($payload),
            default => response()->json(['message' => 'Event ignored']),
        };
    }

    /**
     * Verify the webhook signature from GitHub.
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.github.webhook_secret');

        // Skip verification if no secret is configured (dev mode)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle issue events (opened, closed, reopened, etc.).
     */
    private function handleIssueEvent(array $payload): JsonResponse
    {
        $action = $payload['action'] ?? null;
        $issue = $payload['issue'] ?? null;
        $repository = $payload['repository'] ?? null;

        if (! $issue || ! $repository) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $issueNodeId = $issue['node_id'];
        $issueNumber = $issue['number'];
        $issueState = $issue['state'];
        $repoFullName = $repository['full_name'];

        // Find the external link for this issue by node_id (most reliable)
        $externalLink = ExternalLink::where('provider', IntegrationProvider::GitHub)
            ->where('external_id', $issueNodeId)
            ->first();

        if (! $externalLink) {
            // Fallback: find by issue number and matching integration
            [$owner, $repo] = explode('/', $repoFullName) + [null, null];

            if ($owner && $repo) {
                $externalLink = ExternalLink::where('provider', IntegrationProvider::GitHub)
                    ->where('external_number', $issueNumber)
                    ->whereHas('integration', function ($query) use ($owner, $repo) {
                        $query->where('provider', IntegrationProvider::GitHub)
                            ->where('settings->owner', $owner)
                            ->where('settings->repo', $repo);
                    })
                    ->first();
            }
        }

        if (! $externalLink) {
            Log::info('GitHub webhook: No matching external link found', [
                'issue_node_id' => $issueNodeId,
                'issue_number' => $issueNumber,
                'repo' => $repoFullName,
            ]);

            return response()->json(['message' => 'Issue not tracked']);
        }

        $task = $externalLink->task;
        $integration = $externalLink->integration;

        if (! $task || ! $integration) {
            return response()->json(['message' => 'Task or integration not found']);
        }

        // Update external link state
        $externalLink->update([
            'external_state' => $issueState,
        ]);

        // Handle status changes based on action
        $settings = $integration->settings;

        if ($action === 'closed') {
            $newStatus = match ($settings['sync_closed_as'] ?? 'done') {
                'done' => TaskStatus::Done,
                default => TaskStatus::Done,
            };

            if ($task->status !== $newStatus) {
                $task->updateQuietly(['status' => $newStatus]);

                Log::info('GitHub webhook: Task status updated to done', [
                    'task_id' => $task->id,
                    'issue_number' => $issueNumber,
                ]);
            }
        } elseif ($action === 'reopened') {
            $newStatus = match ($settings['sync_reopened_as'] ?? 'doing') {
                'doing' => TaskStatus::Doing,
                'todo' => TaskStatus::Todo,
                default => TaskStatus::Doing,
            };

            if ($task->status === TaskStatus::Done) {
                $task->updateQuietly(['status' => $newStatus]);

                Log::info('GitHub webhook: Task status updated from done', [
                    'task_id' => $task->id,
                    'issue_number' => $issueNumber,
                    'new_status' => $newStatus->value,
                ]);
            }
        }

        return response()->json([
            'message' => 'Webhook processed',
            'action' => $action,
            'task_id' => $task->id,
        ]);
    }

    /**
     * Handle ping event (sent when webhook is first configured).
     */
    private function handlePing(array $payload): JsonResponse
    {
        Log::info('GitHub webhook ping received', [
            'zen' => $payload['zen'] ?? null,
            'hook_id' => $payload['hook_id'] ?? null,
        ]);

        return response()->json(['message' => 'pong']);
    }
}
