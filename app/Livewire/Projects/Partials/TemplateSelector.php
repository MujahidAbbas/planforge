<?php

namespace App\Livewire\Projects\Partials;

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
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

    /** Search query for filtering templates */
    public string $search = '';

    /** Preview slide-over state */
    public bool $showPreview = false;

    public ?string $previewTemplateId = null;

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

    /** Get user's custom templates, filtered by search */
    #[Computed]
    public function myTemplates(): Collection
    {
        $query = Template::query()
            ->where('user_id', auth()->id())
            ->where('document_type', $this->documentTypeEnum());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /** Get built-in templates grouped by category, filtered by search */
    #[Computed]
    public function builtInGroupedTemplates(): SupportCollection
    {
        $query = Template::query()
            ->where('document_type', $this->documentTypeEnum())
            ->where(function ($query) {
                $query->where('is_built_in', true)
                    ->orWhere('is_community', true)
                    ->orWhere('is_public', true);
            })
            ->whereNull('user_id');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        return $query
            ->orderBy('sort_order')
            ->orderByDesc('is_built_in')
            ->orderByDesc('usage_count')
            ->get()
            ->groupBy(fn (Template $template): string => $template->category?->value ?? 'other')
            ->map(fn (Collection $templates, string $category): array => [
                'category' => TemplateCategory::tryFrom($category),
                'templates' => $templates,
            ]);
    }

    /** Get the recommended template (PlanForge PRD or Tech Spec) */
    #[Computed]
    public function recommendedTemplate(): ?Template
    {
        $recommendedName = $this->documentType === 'prd' ? 'PlanForge PRD' : 'PlanForge Tech Spec';

        return Template::query()
            ->where('name', $recommendedName)
            ->where('is_built_in', true)
            ->first();
    }

    /** Get quick option templates (No Template placeholder + alternative) */
    #[Computed]
    public function quickOptions(): array
    {
        // Find "No Template" option from core category
        $noTemplate = Template::query()
            ->where('document_type', $this->documentTypeEnum())
            ->where('name', 'No Template')
            ->where('is_built_in', true)
            ->first();

        // Find an alternative template (first non-recommended from core)
        $recommended = $this->recommendedTemplate;
        $alternative = Template::query()
            ->where('document_type', $this->documentTypeEnum())
            ->where('category', TemplateCategory::Core)
            ->where('is_built_in', true)
            ->when($recommended, fn ($q) => $q->where('id', '!=', $recommended->id))
            ->when($noTemplate, fn ($q) => $q->where('id', '!=', $noTemplate->id))
            ->orderBy('sort_order')
            ->first();

        return array_filter([$noTemplate, $alternative]);
    }

    /** Check if there are any search results */
    #[Computed]
    public function hasSearchResults(): bool
    {
        if (! $this->search) {
            return true;
        }

        return $this->myTemplates->isNotEmpty() || $this->builtInGroupedTemplates->isNotEmpty();
    }

    /** Get total template count for display */
    #[Computed]
    public function totalTemplateCount(): int
    {
        return Template::query()
            ->where('document_type', $this->documentTypeEnum())
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_built_in', true)
                    ->orWhere('is_community', true)
                    ->orWhere('is_public', true);
            })
            ->count();
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

    /** Get template being previewed */
    #[Computed]
    public function previewTemplate(): ?Template
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return Template::find($this->previewTemplateId);
    }

    public function selectTemplate(string $templateId): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->authorize('update', $project);

        $this->selectedTemplateId = $templateId;
        $project->update([$this->templateField() => $templateId]);

        // Dispatch with template details for sticky bar
        $template = Template::find($templateId);
        $this->dispatch('template-selected',
            templateId: $templateId,
            templateName: $template?->name ?? 'No Template',
            sectionCount: count($template?->sections ?? [])
        );
    }

    public function clearTemplate(): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->authorize('update', $project);

        $this->selectedTemplateId = null;
        $project->update([$this->templateField() => null]);

        $this->dispatch('template-cleared');
    }

    /** Open preview slide-over for a template */
    public function preview(string $templateId): void
    {
        $this->previewTemplateId = $templateId;
        $this->showPreview = true;
    }

    /** Close preview slide-over */
    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewTemplateId = null;
    }

    /** Select template from preview and close slide-over */
    public function selectFromPreview(): void
    {
        if ($this->previewTemplateId) {
            $this->selectTemplate($this->previewTemplateId);
            $this->closePreview();
        }
    }

    /** Clear search query */
    public function clearSearch(): void
    {
        $this->search = '';
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

            return;
        }

        // Fall back to recommended template if no user default
        $this->applyRecommendedTemplate($project);
    }

    private function applyRecommendedTemplate(Project $project): void
    {
        $recommended = $this->recommendedTemplate;

        if ($recommended) {
            $this->selectedTemplateId = $recommended->id;
            $project->update([$this->templateField() => $recommended->id]);
        }
    }
}
