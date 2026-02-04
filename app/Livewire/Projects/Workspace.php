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
        $this->authorize('view', $project);
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
            auth()->id()
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    public function regeneratePrd(bool $includeDownstream = false): void
    {
        $this->isGenerating = true;

        $run = app(RegeneratePrd::class)->handle(
            $this->project,
            auth()->id(),
            $includeDownstream
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    public function regenerateTech(): void
    {
        $this->isGenerating = true;

        $run = app(RegenerateTech::class)->handle(
            $this->project,
            auth()->id()
        );

        $this->dispatch('planRunStarted', runId: $run->id);
    }

    /**
     * Handle startPlanRun event from child components (PRD/Tech tabs)
     * to trigger generation when a template is selected
     */
    #[On('startPlanRun')]
    public function handleStartPlanRun(): void
    {
        $this->generate();
    }

    #[On('docUpdated')]
    #[On('planRunCompleted')]
    public function refreshProject(): void
    {
        $this->isGenerating = false;
        unset($this->project);
    }

    /**
     * Proxy method to handle stale Livewire requests after OAuth redirects.
     * Dispatches to the child Integrations component.
     */
    public function triggerSync(): void
    {
        $this->dispatch('triggerSync')->to('projects.tabs.integrations');
    }

    public function render()
    {
        return view('livewire.projects.workspace');
    }
}
