<?php

namespace App\Livewire\Templates;

use App\Enums\DocumentType;
use App\Jobs\ParseTemplateContentJob;
use App\Models\Template;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Create extends Component
{
    public int $currentStep = 1;

    public ?string $mode = null;

    public string $pastedContent = '';

    public bool $isParsing = false;

    #[Validate('required|max:255')]
    public string $name = '';

    public string $description = '';

    public string $instructions = '';

    public string $documentType = 'prd';

    public array $sections = [];

    public bool $showPreview = false;

    public function selectMethod(string $method): void
    {
        $this->mode = $method;
        $this->currentStep = 2;

        if ($method === 'manual') {
            $this->sections = [
                ['title' => '', 'description' => ''],
            ];
        }
    }

    public function goBack(): void
    {
        $this->currentStep = 1;
        $this->mode = null;
        $this->reset(['pastedContent', 'name', 'description', 'instructions', 'sections']);
    }

    public function parseContent(): void
    {
        $this->validate(['pastedContent' => 'required|min:50']);
        $this->isParsing = true;

        ParseTemplateContentJob::dispatch($this->pastedContent, $this->cacheKey());
    }

    public function checkParseResult(): void
    {
        $result = Cache::get($this->cacheKey());

        if (! $result) {
            return;
        }

        $this->name = $result['name'] ?? '';
        $this->description = $result['description'] ?? '';
        $this->sections = $result['sections'] ?? [];
        $this->isParsing = false;
        $this->mode = 'manual';

        Cache::forget($this->cacheKey());
    }

    public function addSection(): void
    {
        $this->sections[] = ['title' => '', 'description' => ''];
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function reorderSection(int|string $oldIndex, int|string $newIndex): void
    {
        $oldIndex = (int) $oldIndex;
        $newIndex = (int) $newIndex;

        $section = $this->sections[$oldIndex] ?? null;

        if (! $section) {
            return;
        }

        $sections = $this->sections;
        unset($sections[$oldIndex]);
        $sections = array_values($sections);

        array_splice($sections, $newIndex, 0, [$section]);

        $this->sections = $sections;
    }

    public function openPreview(): void
    {
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|max:255',
            'sections' => 'array',
            'sections.*.title' => 'required|string|max:255',
        ]);

        $sections = array_values(
            array_filter($this->sections, fn (array $section): bool => ! empty($section['title']))
        );

        Template::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'description' => $this->description ?: null,
            'document_type' => DocumentType::from($this->documentType),
            'ai_instructions' => $this->instructions ?: null,
            'sections' => $sections,
            'is_built_in' => false,
            'is_public' => false,
        ]);

        session()->flash('success', 'Template created successfully!');
        $this->redirect(route('templates.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.templates.create');
    }

    private function cacheKey(): string
    {
        return 'template-parse:'.auth()->id().':'.md5($this->pastedContent);
    }
}
