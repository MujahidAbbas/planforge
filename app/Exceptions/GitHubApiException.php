<?php

namespace App\Exceptions;

class GitHubApiException extends \Exception
{
    public function __construct(string $message, int $statusCode = 0)
    {
        parent::__construct($message, $statusCode);
    }
}
