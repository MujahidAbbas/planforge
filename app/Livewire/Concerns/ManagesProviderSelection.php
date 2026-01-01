<?php

namespace App\Livewire\Concerns;

use App\Enums\AiProvider;
use App\Services\ProviderService;
use Livewire\Attributes\Computed;

trait ManagesProviderSelection
{
    public string $selectedProvider = '';

    public string $selectedModel = '';

    public bool $useCustomModel = false;

    public string $customModel = '';

    public function updatedSelectedProvider(): void
    {
        $provider = AiProvider::tryFrom($this->selectedProvider);

        if ($provider) {
            $this->selectedModel = $this->providerService()->getDefaultModel($provider) ?? '';
        }

        $this->useCustomModel = false;
        $this->customModel = '';
    }

    public function updatedSelectedModel(): void
    {
        $this->useCustomModel = ($this->selectedModel === 'custom');
    }

    #[Computed]
    public function availableProviders(): array
    {
        return $this->providerService()->getProviderOptions();
    }

    #[Computed]
    public function modelsForSelectedProvider(): array
    {
        $provider = AiProvider::tryFrom($this->selectedProvider);

        if (! $provider) {
            return [];
        }

        return $this->providerService()->getModelsForProvider($provider);
    }

    #[Computed]
    public function hasProviders(): bool
    {
        return $this->providerService()->hasAvailableProviders();
    }

    protected function initializeProviderDefaults(): void
    {
        $service = $this->providerService();
        $defaultProvider = $service->getDefaultProvider();

        if ($defaultProvider) {
            $this->selectedProvider = $defaultProvider->value;
            $this->selectedModel = $service->getDefaultModel($defaultProvider) ?? '';
        }
    }

    protected function initializeFromProject(string $currentProvider, ?string $currentModel): void
    {
        $service = $this->providerService();

        // Set current provider or fall back to default
        $this->selectedProvider = $currentProvider ?: ($service->getDefaultProvider()?->value ?? '');

        // Check if current model is in curated list
        $provider = AiProvider::tryFrom($this->selectedProvider);

        if (! $provider) {
            return;
        }

        $curatedModels = $service->getModelsForProvider($provider);

        if ($currentModel && ! isset($curatedModels[$currentModel])) {
            // Current model is custom
            $this->useCustomModel = true;
            $this->customModel = $currentModel;
            $this->selectedModel = 'custom';
        } else {
            $this->selectedModel = $currentModel ?: ($service->getDefaultModel($provider) ?? '');
            $this->useCustomModel = false;
            $this->customModel = '';
        }
    }

    protected function getProviderValidationRules(): array
    {
        $rules = [
            'selectedProvider' => 'required',
            'selectedModel' => 'required',
        ];

        if ($this->useCustomModel) {
            $rules['customModel'] = 'required|string|min:3|max:100';
        }

        return $rules;
    }

    protected function getFinalModel(): string
    {
        return $this->useCustomModel ? $this->customModel : $this->selectedModel;
    }

    protected function providerService(): ProviderService
    {
        return app(ProviderService::class);
    }
}
