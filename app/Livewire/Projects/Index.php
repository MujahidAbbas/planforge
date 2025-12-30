<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $name = '';

    public string $idea = '';

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'idea']);
    }

    public function createProject(): void
    {
        $this->validate([
            'name' => 'required|min:3|max:255',
            'idea' => 'required|min:10',
        ]);

        // For now, use user_id = 1 (test user) since we don't have auth yet
        $project = Project::create([
            'user_id' => 1,
            'name' => $this->name,
            'idea' => $this->idea,
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
