<?php

namespace App\Exceptions;

class GitHubRateLimitException extends GitHubApiException
{
    public int $retryAfter;

    public function __construct(string $message, int $retryAfter = 60)
    {
        parent::__construct($message, 429);
        $this->retryAfter = $retryAfter;
    }
}
