<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\Integration;
use App\Models\Project;
use App\Models\User;
use App\Services\GitHub\GitHubApiService;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GitHubIntegrationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Redirect to GitHub App installation.
     */
    public function install(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $state = encrypt([
            'project_id' => $project->id,
            'user_id' => auth()->id(),
        ]);

        $appSlug = config('services.github.app_slug');

        return redirect("https://github.com/apps/{$appSlug}/installations/new?state={$state}");
    }

    /**
     * Handle callback after GitHub App installation.
     *
     * Note: This route runs without auth middleware since it's called by GitHub.
     * We re-authenticate the user from the encrypted state.
     */
    public function callback(Request $request): RedirectResponse
    {
        $installationId = $request->input('installation_id');

        if (! $installationId) {
            return redirect()->route('projects.index')
                ->with('error', 'GitHub installation was cancelled.');
        }

        try {
            $state = decrypt($request->input('state'));
        } catch (\Exception $e) {
            return redirect()->route('projects.index')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        $project = Project::findOrFail($state['project_id']);
        $user = User::findOrFail($state['user_id']);

        // Re-authenticate the user from state
        auth()->login($user);

        // Now we can authorize
        $this->authorize('update', $project);

        // Store installation ID temporarily
        session(['github_installation_id' => $installationId]);

        return redirect()->route('integrations.github.select-repo', $project);
    }

    /**
     * Show repository selection page.
     */
    public function selectRepo(Request $request, Project $project, GitHubApiService $api): View|RedirectResponse
    {
        $this->authorize('update', $project);

        $installationId = session('github_installation_id');

        if (! $installationId) {
            return redirect()->route('integrations.github.install', $project)
                ->with('error', 'GitHub installation expired. Please try again.');
        }

        try {
            $repos = $api->listRepositories($installationId);
        } catch (\Exception $e) {
            return redirect()->route('projects.workspace', $project)
                ->with('error', 'Failed to fetch repositories: '.$e->getMessage());
        }

        return view('integrations.github.select-repo', [
            'project' => $project,
            'repositories' => $repos['repositories'] ?? [],
        ]);
    }

    /**
     * Complete setup with selected repository.
     */
    public function setup(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'owner' => 'required|string',
            'repo' => 'required|string',
            'default_labels' => 'nullable|array',
            'sync_closed_as' => 'nullable|string|in:done',
            'sync_reopened_as' => 'nullable|string|in:doing,todo',
        ]);

        $installationId = session('github_installation_id');

        if (! $installationId) {
            return redirect()->route('integrations.github.install', $project)
                ->with('error', 'GitHub installation expired. Please try again.');
        }

        Integration::updateOrCreate(
            [
                'project_id' => $project->id,
                'provider' => IntegrationProvider::GitHub,
            ],
            [
                'status' => IntegrationStatus::Connected,
                'settings' => [
                    'installation_id' => $installationId,
                    'owner' => $validated['owner'],
                    'repo' => $validated['repo'],
                    'default_labels' => $validated['default_labels'] ?? ['planforge'],
                    'sync_closed_as' => $validated['sync_closed_as'] ?? 'done',
                    'sync_reopened_as' => $validated['sync_reopened_as'] ?? 'doing',
                ],
                'error_message' => null,
            ]
        );

        session()->forget('github_installation_id');

        return redirect()->route('projects.workspace', $project)
            ->with('success', 'GitHub integration connected successfully!');
    }

    /**
     * Disconnect GitHub integration.
     */
    public function disconnect(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $integration = $project->gitHubIntegration();

        if ($integration) {
            $integration->update([
                'status' => IntegrationStatus::Disabled,
                'error_message' => 'Disconnected by user',
            ]);
        }

        return redirect()->route('projects.workspace', $project)
            ->with('success', 'GitHub integration disconnected.');
    }

    /**
     * Trigger manual sync to GitHub.
     */
    public function sync(Request $request, Project $project, GitHubSyncService $syncService): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $project);

        $integration = $project->gitHubIntegration();

        if (! $integration || ! $integration->isConnected()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'GitHub integration not connected',
                ], 400);
            }

            return redirect()->back()
                ->with('error', 'GitHub integration not connected.');
        }

        $syncRun = $syncService->syncProject($integration, auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sync started',
                'sync_run_id' => $syncRun->id,
                'total_tasks' => $syncRun->total_count,
            ]);
        }

        return redirect()->back()
            ->with('success', "Syncing {$syncRun->total_count} tasks to GitHub...");
    }

    /**
     * Get sync status.
     */
    public function syncStatus(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $integration = $project->gitHubIntegration();

        if (! $integration) {
            return response()->json([
                'connected' => false,
            ]);
        }

        $latestRun = $integration->syncRuns()->latest()->first();

        return response()->json([
            'connected' => $integration->isConnected(),
            'status' => $integration->status->value,
            'repo' => $integration->getRepoFullName(),
            'last_synced_at' => $integration->last_synced_at?->toISOString(),
            'latest_run' => $latestRun ? [
                'id' => $latestRun->id,
                'status' => $latestRun->status->value,
                'total' => $latestRun->total_count,
                'created' => $latestRun->created_count,
                'updated' => $latestRun->updated_count,
                'failed' => $latestRun->failed_count,
                'started_at' => $latestRun->started_at->toISOString(),
                'completed_at' => $latestRun->completed_at?->toISOString(),
            ] : null,
        ]);
    }
}
