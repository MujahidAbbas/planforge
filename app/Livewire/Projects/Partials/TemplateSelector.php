<?php

namespace App\Livewire\Projects\Partials;

use App\Enums\DocumentType;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

        // Load current selection from project
        $project = Project::findOrFail($projectId);
        $fieldName = $documentType === 'prd' ? 'prd_template_id' : 'tech_template_id';
        $this->selectedTemplateId = $project->{$fieldName};
    }

    #[Computed]
    public function templates(): Collection
    {
        $type = DocumentType::from($this->documentType);

        return Template::getAvailable($type, auth()->id());
    }

    #[Computed]
    public function groupedTemplates(): \Illuminate\Support\Collection
    {
        $type = DocumentType::from($this->documentType);

        return Template::getGroupedByCategory($type, auth()->id());
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

        // Update project
        $fieldName = $this->documentType === 'prd' ? 'prd_template_id' : 'tech_template_id';
        $project->update([$fieldName => $templateId]);

        $this->dispatch('template-selected', templateId: $templateId);
    }

    public function clearTemplate(): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->authorize('update', $project);

        $this->selectedTemplateId = null;

        $fieldName = $this->documentType === 'prd' ? 'prd_template_id' : 'tech_template_id';
        $project->update([$fieldName => null]);

        $this->dispatch('template-cleared');
    }

    public function render()
    {
        return view('livewire.projects.partials.template-selector');
    }
}
