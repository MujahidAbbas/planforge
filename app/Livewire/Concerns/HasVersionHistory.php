<?php

namespace App\Livewire\Concerns;

use App\Models\DocumentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait HasVersionHistory
{
    public bool $showVersionHistory = false;

    public ?string $previewVersionId = null;

    public function openVersionHistory(): void
    {
        $this->showVersionHistory = true;
        $this->previewVersionId = null;
    }

    public function closeVersionHistory(): void
    {
        $this->showVersionHistory = false;
        $this->previewVersionId = null;
    }

    public function selectVersion(string $versionId): void
    {
        $this->previewVersionId = $versionId;
    }

    public function restoreVersion(string $versionId): void
    {
        $oldVersion = DocumentVersion::findOrFail($versionId);
        $document = $this->document;

        if (! $document) {
            return;
        }

        // Verify version belongs to this document
        if ($oldVersion->document_id !== $document->id) {
            return;
        }

        // Verify user has permission to update this project
        $this->authorize('update', $document->project);

        // Wrap in transaction for atomicity
        DB::transaction(function () use ($oldVersion, $document) {
            // Create new version from old content
            $newVersion = DocumentVersion::create([
                'document_id' => $document->id,
                'created_by' => auth()->id(),
                'content_md' => $oldVersion->content_md,
                'summary' => 'Restored from '.$oldVersion->created_at->format('M j, Y g:i A'),
            ]);

            // Update document to point to new version
            $document->update(['current_version_id' => $newVersion->id]);
        });

        // Refresh UI state
        $this->loadContent();
        unset($this->document);
        unset($this->versions);

        $this->closeVersionHistory();

        // Notify other components
        $this->dispatch('docUpdated', type: $document->type->value);
        $this->dispatch('version-restored', message: 'Version restored successfully');
    }

    #[Computed]
    public function versions(): Collection
    {
        if (! $this->document) {
            return collect();
        }

        return $this->document
            ->versions()
            ->with(['createdBy', 'planRun'])
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function selectedVersionForPreview(): ?DocumentVersion
    {
        if (! $this->previewVersionId) {
            return null;
        }

        return $this->versions->firstWhere('id', $this->previewVersionId);
    }
}
