<?php

namespace App\Livewire\Templates;

use App\Enums\DocumentType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
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
        return Template::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function builtInTemplates(): Collection
    {
        return Template::query()
            ->where('is_built_in', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function previewTemplate(): ?Template
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return Template::find($this->previewTemplateId);
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
        $field = $this->defaultTemplateField($template->document_type);

        auth()->user()->update([$field => $templateId]);
    }

    public function clearDefault(string $documentType): void
    {
        $field = $this->defaultTemplateField(DocumentType::from($documentType));

        auth()->user()->update([$field => null]);
    }

    public function deleteTemplate(string $templateId): void
    {
        $template = Template::query()
            ->where('id', $templateId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $template) {
            return;
        }

        $this->clearDefaultIfMatches($templateId);
        $template->delete();
    }

    public function render(): View
    {
        return view('livewire.templates.index');
    }

    private function defaultTemplateField(DocumentType $type): string
    {
        return $type === DocumentType::Prd
            ? 'default_prd_template_id'
            : 'default_tech_template_id';
    }

    private function clearDefaultIfMatches(string $templateId): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->default_prd_template_id === $templateId) {
            $user->update(['default_prd_template_id' => null]);
        }

        if ($user->default_tech_template_id === $templateId) {
            $user->update(['default_tech_template_id' => null]);
        }
    }
}
