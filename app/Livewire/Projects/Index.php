<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesProviderSelection;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use ManagesProviderSelection;

    public string $name = '';

    public string $idea = '';

    public bool $showCreateModal = false;

    public function mount(): void
    {
        $this->initializeProviderDefaults();
    }

    public function openCreateModal(): void
    {
        $this->initializeProviderDefaults();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'idea', 'selectedProvider', 'selectedModel', 'useCustomModel', 'customModel']);
    }

    public function createProject(): void
    {
        $rules = array_merge(
            [
                'name' => 'required|min:3|max:255',
                'idea' => 'required|min:10',
            ],
            $this->getProviderValidationRules()
        );

        $this->validate($rules);

        // For now, use user_id = 1 (test user) since we don't have auth yet
        $project = Project::create([
            'user_id' => 1,
            'name' => $this->name,
            'idea' => $this->idea,
            'preferred_provider' => $this->selectedProvider,
            'preferred_model' => $this->getFinalModel(),
            'status' => ProjectStatus::Active,
        ]);

        $this->closeCreateModal();
        $this->redirect(route('projects.workspace', $project), navigate: true);
    }

    public function render()
    {
        // For now, show all projects (would filter by auth user later)
        $projects = Project::query()
            ->where('status', ProjectStatus::Active)
            ->latest()
            ->get();

        return view('livewire.projects.index', [
            'projects' => $projects,
        ]);
    }
}
