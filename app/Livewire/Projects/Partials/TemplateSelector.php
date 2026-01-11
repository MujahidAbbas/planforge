<?php

namespace App\Livewire\Projects\Partials;

use App\Enums\DocumentType;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TemplateSelector extends Component
{
    use AuthorizesRequests;

    public string $projectId;

    public string $documentType;

    public ?string $selectedTemplateId = null;

    public function mount(string $projectId, string $documentType): void
    {
        $this->projectId = $projectId;
        $this->documentType = $documentType;

        $project = Project::findOrFail($projectId);
        $this->selectedTemplateId = $project->{$this->templateField()};

        if (! $this->selectedTemplateId) {
            $this->applyUserDefaultTemplate($project);
        }
    }

    #[Computed]
    public function templates(): Collection
    {
        return Template::getAvailable($this->documentTypeEnum(), auth()->id());
    }

    #[Computed]
    public function groupedTemplates(): SupportCollection
    {
        return Template::getGroupedByCategory($this->documentTypeEnum(), auth()->id());
    }

    #[Computed]
    public function sectionCount(): int
    {
        return count($this->selectedTemplate?->sections ?? []);
    }

    #[Computed]
    public function selectedTemplate(): ?Template
    {
        if (! $this->selectedTemplateId) {
            return null;
        }

        return Template::find($this->selectedTemplateId);
    }

    public function selectTemplate(string $templateId): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->authorize('update', $project);

        $this->selectedTemplateId = $templateId;
        $project->update([$this->templateField() => $templateId]);

        $this->dispatch('template-selected', templateId: $templateId);
    }

    public function clearTemplate(): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->authorize('update', $project);

        $this->selectedTemplateId = null;
        $project->update([$this->templateField() => null]);

        $this->dispatch('template-cleared');
    }

    public function render(): View
    {
        return view('livewire.projects.partials.template-selector');
    }

    private function templateField(): string
    {
        return $this->documentType === 'prd' ? 'prd_template_id' : 'tech_template_id';
    }

    private function defaultTemplateField(): string
    {
        return $this->documentType === 'prd' ? 'default_prd_template_id' : 'default_tech_template_id';
    }

    private function documentTypeEnum(): DocumentType
    {
        return DocumentType::from($this->documentType);
    }

    private function applyUserDefaultTemplate(Project $project): void
    {
        $defaultTemplateId = auth()->user()->{$this->defaultTemplateField()};

        if ($defaultTemplateId) {
            $this->selectedTemplateId = $defaultTemplateId;
            $project->update([$this->templateField() => $defaultTemplateId]);
        }
    }
}
