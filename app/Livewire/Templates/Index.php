<?php

namespace App\Livewire\Templates;

use App\Enums\DocumentType;
use App\Models\Template;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public ?string $previewTemplateId = null;

    #[Computed]
    public function customTemplates(): Collection
    {
        return Template::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function builtInTemplates(): Collection
    {
        return Template::where('is_built_in', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function previewTemplate(): ?Template
    {
        return $this->previewTemplateId
            ? Template::find($this->previewTemplateId)
            : null;
    }

    public function preview(string $templateId): void
    {
        $this->previewTemplateId = $templateId;
    }

    public function closePreview(): void
    {
        $this->previewTemplateId = null;
    }

    public function makeDefault(string $templateId): void
    {
        $template = Template::findOrFail($templateId);
        $field = $template->document_type === DocumentType::Prd
            ? 'default_prd_template_id'
            : 'default_tech_template_id';

        auth()->user()->update([$field => $templateId]);
    }

    public function clearDefault(string $documentType): void
    {
        $field = $documentType === 'prd'
            ? 'default_prd_template_id'
            : 'default_tech_template_id';

        auth()->user()->update([$field => null]);
    }

    public function deleteTemplate(string $templateId): void
    {
        $template = Template::where('id', $templateId)
            ->where('user_id', auth()->id())
            ->first();

        if ($template) {
            // Clear default if this template was set as default
            $user = auth()->user();
            if ($user->default_prd_template_id === $templateId) {
                $user->update(['default_prd_template_id' => null]);
            }
            if ($user->default_tech_template_id === $templateId) {
                $user->update(['default_tech_template_id' => null]);
            }

            $template->delete();
        }
    }

    public function render()
    {
        return view('livewire.templates.index');
    }
}
