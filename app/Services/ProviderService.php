<?php

namespace App\Services;

use App\Enums\AiProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;

class ProviderService
{
    private ?Collection $cachedAvailableProviders = null;

    /**
     * Get all providers that have API keys configured.
     *
     * @return Collection<AiProvider>
     */
    public function getAvailableProviders(): Collection
    {
        if ($this->cachedAvailableProviders !== null) {
            return $this->cachedAvailableProviders;
        }

        $this->cachedAvailableProviders = collect(AiProvider::cases())
            ->filter(fn (AiProvider $provider) => $this->isProviderAvailable($provider));

        return $this->cachedAvailableProviders;
    }

    /**
     * Get the first available provider as default.
     */
    public function getDefaultProvider(): ?AiProvider
    {
        return $this->getAvailableProviders()->first();
    }

    /**
     * Get the default model for a provider.
     */
    public function getDefaultModel(AiProvider $provider): ?string
    {
        return config("providers.{$provider->value}.default");
    }

    /**
     * Get curated model list for a provider.
     *
     * @return array<string, string> Model ID => Display Name
     */
    public function getModelsForProvider(AiProvider $provider): array
    {
        return config("providers.{$provider->value}.models", []);
    }

    /**
     * Check if a provider has an API key configured.
     */
    public function isProviderAvailable(AiProvider $provider): bool
    {
        // Ollama doesn't need an API key, just a URL
        if ($provider->isLocal()) {
            $url = config('prism.providers.ollama.url');

            return ! empty($url);
        }

        $apiKey = config($provider->configKey());

        return ! empty($apiKey);
    }

    /**
     * Convert a provider string to Prism Provider enum.
     * Falls back to default provider if invalid.
     */
    public function resolveProvider(?string $providerString): Provider
    {
        if (! $providerString) {
            return $this->getDefaultProviderOrFail()->toPrismProvider();
        }

        $aiProvider = AiProvider::tryFrom($providerString);

        if (! $aiProvider) {
            Log::warning("Unknown AI provider '{$providerString}', falling back to default");

            return $this->getDefaultProviderOrFail()->toPrismProvider();
        }

        return $aiProvider->toPrismProvider();
    }

    /**
     * Get default provider or throw if none configured.
     */
    private function getDefaultProviderOrFail(): AiProvider
    {
        $defaultProvider = $this->getDefaultProvider();

        if (! $defaultProvider) {
            throw new \RuntimeException(
                'No AI providers configured. Please add an API key to your .env file.'
            );
        }

        return $defaultProvider;
    }

    /**
     * Get provider options for select dropdown.
     *
     * @return array<string, string> Provider value => Label
     */
    public function getProviderOptions(): array
    {
        return $this->getAvailableProviders()
            ->mapWithKeys(fn (AiProvider $p) => [$p->value => $p->label()])
            ->toArray();
    }

    /**
     * Check if any providers are available.
     */
    public function hasAvailableProviders(): bool
    {
        return $this->getAvailableProviders()->isNotEmpty();
    }
}
