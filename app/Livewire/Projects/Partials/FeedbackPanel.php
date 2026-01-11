<?php

namespace App\Livewire\Projects\Partials;

use App\Enums\DocumentType;
use App\Enums\FeedbackType;
use App\Jobs\GenerateFeedbackJob;
use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FeedbackPanel extends Component
{
    public string $projectId;

    public string $documentType;

    public ?string $selectedType = null;

    public bool $isLoading = false;

    public bool $showPanel = false;

    public function mount(string $projectId, string $documentType): void
    {
        $this->projectId = $projectId;
        $this->documentType = $documentType;
    }

    #[Computed]
    public function document(): ?Document
    {
        $type = DocumentType::from($this->documentType);

        return Document::query()
            ->where('project_id', $this->projectId)
            ->where('type', $type)
            ->with('currentVersion')
            ->first();
    }

    #[Computed]
    public function feedbackTypes(): array
    {
        return FeedbackType::cases();
    }

    #[Computed]
    public function cacheKey(): string
    {
        $versionId = $this->document?->current_version_id ?? 'none';

        // Include projectId to prevent cache collisions across projects
        return "feedback:{$this->projectId}:{$this->documentType}:{$versionId}:{$this->selectedType}";
    }

    #[Computed]
    public function cachedFeedback(): ?array
    {
        if (! $this->selectedType) {
            return null;
        }

        return Cache::get($this->cacheKey);
    }

    public function getFeedback(string $type): void
    {
        if (! $this->document || ! $this->document->currentVersion) {
            return;
        }

        $this->selectedType = $type;
        $this->showPanel = true;

        // Check cache first
        if ($this->cachedFeedback) {
            $this->isLoading = false;

            return;
        }

        $this->isLoading = true;

        // Dispatch job
        GenerateFeedbackJob::dispatch(
            $this->document->id,
            FeedbackType::from($type),
            $this->cacheKey,
        );
    }

    public function checkFeedback(): void
    {
        if (Cache::has($this->cacheKey)) {
            $this->isLoading = false;
        }
    }

    public function dismissFeedback(): void
    {
        $this->showPanel = false;
        $this->selectedType = null;
        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.projects.partials.feedback-panel');
    }
}
