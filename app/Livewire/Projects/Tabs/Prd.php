<?php

namespace App\Livewire\Projects\Tabs;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\DocumentVersion;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Prd extends Component
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
            ->where('type', DocumentType::Prd)
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
        // Get or create the document
        $document = Document::firstOrCreate(
            [
                'project_id' => $this->projectId,
                'type' => DocumentType::Prd,
            ]
        );

        // Create a new version (append-only)
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'created_by' => 1, // Would be auth()->id() with real auth
            'content_md' => $this->content,
        ]);

        // Point document to new version
        $document->update(['current_version_id' => $version->id]);

        $this->isDirty = false;
        unset($this->document);

        $this->dispatch('docUpdated', type: 'prd');
    }

    #[On('docUpdated')]
    public function handleDocUpdated(string $type): void
    {
        if ($type === 'prd') {
            $this->loadContent();
        }
    }

    #[On('planRunCompleted')]
    public function handlePlanRunCompleted(): void
    {
        unset($this->document);
        $this->loadContent();
    }

    public function render()
    {
        return view('livewire.projects.tabs.prd');
    }
}
