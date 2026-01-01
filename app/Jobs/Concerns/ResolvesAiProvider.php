<?php

namespace App\Jobs\Concerns;

use App\Services\ProviderService;
use Prism\Prism\Enums\Provider;

trait ResolvesAiProvider
{
    protected function resolveProvider(string $provider): Provider
    {
        return app(ProviderService::class)->resolveProvider($provider);
    }
}
