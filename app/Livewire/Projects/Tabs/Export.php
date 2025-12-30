<?php

namespace App\Livewire\Projects\Tabs;

use App\Enums\DocumentType;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Export extends Component
{
    public string $projectId;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[Computed]
    public function project(): Project
    {
        return Project::query()
            ->with(['documents.currentVersion', 'tasks', 'epics.stories'])
            ->whereKey($this->projectId)
            ->firstOrFail();
    }

    public function downloadProjectKit(): mixed
    {
        return $this->redirect(
            route('projects.exports.projectKit', $this->projectId),
            navigate: false
        );
    }

    public function downloadTasksJson(): mixed
    {
        return $this->redirect(
            route('projects.exports.tasksJson', $this->projectId),
            navigate: false
        );
    }

    public function downloadPrdMarkdown(): mixed
    {
        $doc = $this->project->documents->firstWhere('type', DocumentType::Prd);
        $content = $doc?->currentVersion?->content_md ?? '# PRD\n\nNo PRD generated yet.';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'prd.md', ['Content-Type' => 'text/markdown']);
    }

    public function downloadTechMarkdown(): mixed
    {
        $doc = $this->project->documents->firstWhere('type', DocumentType::Tech);
        $content = $doc?->currentVersion?->content_md ?? '# Tech Spec\n\nNo Tech Spec generated yet.';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'tech-spec.md', ['Content-Type' => 'text/markdown']);
    }

    public function render()
    {
        return view('livewire.projects.tabs.export');
    }
}
