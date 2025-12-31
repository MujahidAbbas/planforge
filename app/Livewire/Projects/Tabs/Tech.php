<?php

namespace App\Livewire\Projects\Tabs;

use App\Actions\GenerateTasksFromTechSpec;
use App\Enums\DocumentType;
use App\Enums\PlanRunStepStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\TaskSet;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Tech extends Component
{
    public string $projectId;

    public string $content = '';

    public bool $isDirty = false;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
        $this->loadContent();
    }

    #[Computed]
    public function document(): ?Document
    {
        return Document::query()
            ->where('project_id', $this->projectId)
            ->where('type', DocumentType::Tech)
            ->with('currentVersion')
            ->first();
    }

    public function loadContent(): void
    {
        $doc = $this->document;
        $this->content = $doc?->currentVersion?->content_md ?? '';
        $this->isDirty = false;
    }

    public function updatedContent(): void
    {
        $this->isDirty = true;
    }

    public function save(): void
    {
        $document = Document::firstOrCreate(
            [
                'project_id' => $this->projectId,
                'type' => DocumentType::Tech,
            ]
        );

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'created_by' => 1,
            'content_md' => $this->content,
        ]);

        $document->update(['current_version_id' => $version->id]);

        $this->isDirty = false;
        unset($this->document);

        $this->dispatch('docUpdated', type: 'tech');
    }

    #[On('docUpdated')]
    public function handleDocUpdated(string $type): void
    {
        if ($type === 'tech') {
            $this->loadContent();
        }
    }

    #[On('planRunCompleted')]
    public function handlePlanRunCompleted(): void
    {
        unset($this->document);
        $this->loadContent();
    }

    #[Computed]
    public function latestTaskSet(): ?TaskSet
    {
        return TaskSet::where('project_id', $this->projectId)
            ->with('planRunStep')
            ->latest()
            ->first();
    }

    #[Computed]
    public function isGeneratingTasks(): bool
    {
        $taskSet = $this->latestTaskSet;

        if (! $taskSet) {
            return false;
        }

        return in_array($taskSet->status, [
            PlanRunStepStatus::Queued,
            PlanRunStepStatus::Running,
            PlanRunStepStatus::Delayed,
        ]);
    }

    public function generateTasks(): void
    {
        if ($this->isGeneratingTasks) {
            return;
        }

        $project = Project::findOrFail($this->projectId);
        $action = new GenerateTasksFromTechSpec;
        $action->handle($project);

        unset($this->latestTaskSet);
        unset($this->isGeneratingTasks);

        $this->dispatch('taskGenerationStarted');
    }

    #[On('taskGenerationCompleted')]
    public function handleTaskGenerationCompleted(): void
    {
        unset($this->latestTaskSet);
        unset($this->isGeneratingTasks);
    }

    public function render()
    {
        return view('livewire.projects.tabs.tech');
    }
}
