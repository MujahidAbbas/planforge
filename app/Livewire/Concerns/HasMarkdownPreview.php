<?php

namespace App\Livewire\Concerns;

use App\Services\MarkdownService;
use Livewire\Attributes\Computed;

trait HasMarkdownPreview
{
    public string $editorMode = 'write';

    public function setEditorMode(string $mode): void
    {
        if (! in_array($mode, ['write', 'preview', 'split'])) {
            return;
        }

        $this->editorMode = $mode;
        unset($this->previewHtml);
    }

    #[Computed]
    public function previewHtml(): string
    {
        return app(MarkdownService::class)->render($this->content);
    }

    protected function clearPreviewCache(): void
    {
        unset($this->previewHtml);
    }
}
