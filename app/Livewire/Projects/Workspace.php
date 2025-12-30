<?php

namespace App\Livewire\Projects;

use App\Actions\RegeneratePrd;
use App\Actions\RegenerateTech;
use App\Actions\StartPlanRun;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Workspace extends Component
{
    public string $projectId;

    #[Url(as: 'tab', except: 'prd')]
    public string $tab = 'prd';

    public bool $isGenerating = false;

    public function mount(Project $project): void
    {
        // Authorization disabled for now - add back when auth is set up
        // $this->authorize('view', $project);
        $this->projectId = $project->id;
    }

    #[Computed]
    public function project(): Project
    {
        return Project::query()
            ->with(['planRuns' => fn ($q) => $q->latest()->limit(1)])
            ->whereKey($this->projectId)
            ->firstOrFail();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function generate(): void
    {
        $this->isGenerating = true;

        $run = app(StartPlanRun::class)->handle(
            $this->project,
            1 // Would be auth()->id() with real auth
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    public function regeneratePrd(bool $includeDownstream = false): void
    {
        $this->isGenerating = true;

        $run = app(RegeneratePrd::class)->handle(
            $this->project,
            1, // Would be auth()->id() with real auth
            $includeDownstream
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    public function regenerateTech(): void
    {
        $this->isGenerating = true;

        $run = app(RegenerateTech::class)->handle(
            $this->project,
            1 // Would be auth()->id() with real auth
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    #[On('docUpdated')]
    #[On('planRunCompleted')]
    public function refreshProject(): void
    {
        $this->isGenerating = false;
        unset($this->project);
    }

    public function render()
    {
        return view('livewire.projects.workspace');
    }
}
