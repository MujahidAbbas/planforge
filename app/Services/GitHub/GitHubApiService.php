<?php

namespace App\Services\GitHub;

use App\Exceptions\GitHubApiException;
use App\Exceptions\GitHubRateLimitException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubApiService
{
    public function __construct(
        private GitHubAuthService $auth,
        private GitHubRateLimiter $rateLimiter
    ) {}

    /**
     * Create a new issue.
     *
     * @return array<string, mixed>
     */
    public function createIssue(string $installationId, string $owner, string $repo, array $data): array
    {
        return $this->post(
            $installationId,
            "/repos/{$owner}/{$repo}/issues",
            $data
        );
    }

    /**
     * Update an existing issue.
     *
     * @return array<string, mixed>
     */
    public function updateIssue(string $installationId, string $owner, string $repo, int $issueNumber, array $data): array
    {
        return $this->patch(
            $installationId,
            "/repos/{$owner}/{$repo}/issues/{$issueNumber}",
            $data
        );
    }

    /**
     * Close an issue.
     *
     * @return array<string, mixed>
     */
    public function closeIssue(string $installationId, string $owner, string $repo, int $issueNumber): array
    {
        return $this->updateIssue($installationId, $owner, $repo, $issueNumber, [
            'state' => 'closed',
        ]);
    }

    /**
     * Get issue by number.
     *
     * @return array<string, mixed>
     */
    public function getIssue(string $installationId, string $owner, string $repo, int $issueNumber): array
    {
        return $this->get(
            $installationId,
            "/repos/{$owner}/{$repo}/issues/{$issueNumber}"
        );
    }

    /**
     * List available repositories for installation.
     *
     * @return array<string, mixed>
     */
    public function listRepositories(string $installationId): array
    {
        return $this->get($installationId, '/installation/repositories');
    }

    /**
     * List labels for a repository.
     *
     * @return array<string, mixed>
     */
    public function listLabels(string $installationId, string $owner, string $repo): array
    {
        return $this->get($installationId, "/repos/{$owner}/{$repo}/labels");
    }

    /**
     * Create a label if it doesn't exist.
     *
     * @return array<string, mixed>
     */
    public function createLabel(string $installationId, string $owner, string $repo, string $name, string $color = 'ededed'): array
    {
        return $this->post($installationId, "/repos/{$owner}/{$repo}/labels", [
            'name' => $name,
            'color' => $color,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HTTP Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function get(string $installationId, string $endpoint): array
    {
        return $this->request('GET', $installationId, $endpoint);
    }

    /**
     * @return array<string, mixed>
     */
    private function post(string $installationId, string $endpoint, array $data): array
    {
        $this->checkRateLimit();

        return $this->request('POST', $installationId, $endpoint, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function patch(string $installationId, string $endpoint, array $data): array
    {
        $this->checkRateLimit();

        return $this->request('PATCH', $installationId, $endpoint, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $installationId, string $endpoint, array $data = []): array
    {
        $token = $this->auth->getInstallationToken($installationId);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ])->{strtolower($method)}("https://api.github.com{$endpoint}", $data);

        $this->handleRateLimitHeaders($response);

        if ($response->status() === 403 && str_contains($response->body(), 'rate limit')) {
            $this->auth->invalidateToken($installationId);
            throw new GitHubRateLimitException($response->json('message'));
        }

        if ($response->failed()) {
            throw new GitHubApiException(
                $response->json('message', 'Unknown error'),
                $response->status()
            );
        }

        return $response->json();
    }

    private function checkRateLimit(): void
    {
        if (! $this->rateLimiter->canMakeRequest()) {
            throw new GitHubRateLimitException(
                'Rate limit reached. Remaining: '.
                $this->rateLimiter->getRemainingHour().'/hour'
            );
        }

        $this->rateLimiter->recordRequest();
    }

    private function handleRateLimitHeaders(Response $response): void
    {
        $remaining = $response->header('X-RateLimit-Remaining');
        $reset = $response->header('X-RateLimit-Reset');

        if ($remaining !== null && (int) $remaining < 100) {
            logger()->warning('GitHub API rate limit low', [
                'remaining' => $remaining,
                'reset' => $reset,
            ]);
        }
    }
}
