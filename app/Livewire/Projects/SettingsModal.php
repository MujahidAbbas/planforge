<?php

namespace App\Livewire\Projects;

use App\Enums\AiProvider;
use App\Livewire\Concerns\ManagesProviderSelection;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class SettingsModal extends Component
{
    use ManagesProviderSelection;

    public bool $show = false;

    public ?string $projectId = null;

    #[On('openSettings')]
    public function open(string $projectId): void
    {
        $this->projectId = $projectId;
        $project = Project::findOrFail($projectId);

        $this->initializeFromProject(
            $project->preferred_provider ?? '',
            $project->preferred_model
        );

        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->reset(['projectId', 'selectedProvider', 'selectedModel', 'useCustomModel', 'customModel']);
    }

    public function save(): void
    {
        $this->validate($this->getProviderValidationRules());

        $project = Project::findOrFail($this->projectId);
        $project->update([
            'preferred_provider' => $this->selectedProvider,
            'preferred_model' => $this->getFinalModel(),
        ]);

        $this->dispatch('settingsSaved');
        $this->close();
    }

    #[Computed]
    public function currentProviderLabel(): string
    {
        $provider = AiProvider::tryFrom($this->selectedProvider);

        return $provider?->label() ?? 'Unknown';
    }

    public function render()
    {
        return view('livewire.projects.settings-modal');
    }
}
