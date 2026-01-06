<?php

namespace App\Services\GitHub;

use App\Exceptions\GitHubAuthException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GitHubAuthService
{
    private readonly ?string $appId;

    private readonly ?string $privateKey;

    public function __construct(?string $appId = null, ?string $privateKey = null)
    {
        $this->appId = $appId ?? config('services.github.app_id');
        $this->privateKey = $privateKey ?? config('services.github.private_key');
    }

    /**
     * Generate JWT for GitHub App authentication.
     * JWT expires in 10 minutes (GitHub maximum).
     */
    public function generateJWT(): string
    {
        $now = time();

        $payload = [
            'iat' => $now - 60,        // Issued 60s ago (clock drift)
            'exp' => $now + (10 * 60), // Expires in 10 minutes
            'iss' => $this->appId,     // GitHub App ID
        ];

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    /**
     * Get installation access token (cached for 50 minutes).
     */
    public function getInstallationToken(string $installationId): string
    {
        $cacheKey = "github_installation_token_{$installationId}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($installationId) {
            $jwt = $this->generateJWT();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->post("https://api.github.com/app/installations/{$installationId}/access_tokens");

            if ($response->failed()) {
                throw new GitHubAuthException('Failed to get installation token: '.$response->body());
            }

            return $response->json('token');
        });
    }

    /**
     * Invalidate cached token (call when token fails).
     */
    public function invalidateToken(string $installationId): void
    {
        Cache::forget("github_installation_token_{$installationId}");
    }
}
