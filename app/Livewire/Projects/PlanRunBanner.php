<?php

namespace App\Livewire\Projects;

use App\Enums\PlanRunStatus;
use App\Models\PlanRun;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanRunBanner extends Component
{
    public string $projectId;

    public ?string $activeRunId = null;

    private ?bool $wasRunning = null;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;

        // Check if there's already an active run
        $activeRun = PlanRun::query()
            ->where('project_id', $projectId)
            ->whereIn('status', [PlanRunStatus::Queued, PlanRunStatus::Running])
            ->latest()
            ->first();

        if ($activeRun) {
            $this->activeRunId = $activeRun->id;
        }
    }

    #[On('planRunStarted')]
    public function onPlanRunStarted(string $runId): void
    {
        $this->activeRunId = $runId;
        unset($this->latestRun);
    }

    #[Computed]
    public function latestRun(): ?PlanRun
    {
        return PlanRun::query()
            ->where('project_id', $this->projectId)
            ->with(['steps' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->first();
    }

    #[Computed]
    public function isRunning(): bool
    {
        $run = $this->latestRun;

        return $run && in_array($run->status, [PlanRunStatus::Queued, PlanRunStatus::Running]);
    }

    public function poll(): void
    {
        // Clear computed cache to get fresh data
        unset($this->latestRun);

        $currentlyRunning = $this->isRunning;

        // Detect transition from running to not running
        if ($this->wasRunning === true && ! $currentlyRunning) {
            $this->activeRunId = null;
            $this->dispatch('planRunCompleted');
        }

        $this->wasRunning = $currentlyRunning;
    }

    public function render()
    {
        // Track running state for next poll
        if ($this->wasRunning === null) {
            $this->wasRunning = $this->isRunning;
        }

        return view('livewire.projects.plan-run-banner');
    }
}
