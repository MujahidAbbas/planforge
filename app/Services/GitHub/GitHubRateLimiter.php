<?php

namespace App\Services\GitHub;

use Illuminate\Support\Facades\RateLimiter;

class GitHubRateLimiter
{
    private const MINUTE_LIMIT = 60;

    private const HOUR_LIMIT = 400;

    private const MINUTE_KEY = 'github_api_minute';

    private const HOUR_KEY = 'github_api_hour';

    public function canMakeRequest(): bool
    {
        return ! RateLimiter::tooManyAttempts(self::MINUTE_KEY, self::MINUTE_LIMIT)
            && ! RateLimiter::tooManyAttempts(self::HOUR_KEY, self::HOUR_LIMIT);
    }

    public function recordRequest(): void
    {
        RateLimiter::hit(self::MINUTE_KEY, 60);  // Decays after 1 minute
        RateLimiter::hit(self::HOUR_KEY, 3600); // Decays after 1 hour
    }

    public function getRemainingMinute(): int
    {
        return RateLimiter::remaining(self::MINUTE_KEY, self::MINUTE_LIMIT);
    }

    public function getRemainingHour(): int
    {
        return RateLimiter::remaining(self::HOUR_KEY, self::HOUR_LIMIT);
    }

    public function getRetryAfterMinute(): int
    {
        return RateLimiter::availableIn(self::MINUTE_KEY);
    }

    public function getRetryAfterHour(): int
    {
        return RateLimiter::availableIn(self::HOUR_KEY);
    }

    public function clear(): void
    {
        RateLimiter::clear(self::MINUTE_KEY);
        RateLimiter::clear(self::HOUR_KEY);
    }
}
