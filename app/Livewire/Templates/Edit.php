<?php

namespace App\Livewire\Templates;

use App\Models\Template;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Edit extends Component
{
    public Template $template;

    #[Validate('required|max:255')]
    public string $name = '';

    public string $description = '';

    public string $instructions = '';

    public array $sections = [];

    public bool $showPreview = false;

    public function mount(Template $template): void
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $this->template = $template;
        $this->name = $template->name;
        $this->description = $template->description ?? '';
        $this->instructions = $template->ai_instructions ?? '';
        $this->sections = $template->sections ?? [];
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

        $this->template->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'ai_instructions' => $this->instructions ?: null,
            'sections' => $sections,
        ]);

        session()->flash('success', 'Template updated successfully!');
        $this->redirect(route('templates.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->clearDefaultIfMatches();
        $this->template->delete();

        session()->flash('success', 'Template deleted successfully!');
        $this->redirect(route('templates.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.templates.edit');
    }

    private function clearDefaultIfMatches(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->default_prd_template_id === $this->template->id) {
            $user->update(['default_prd_template_id' => null]);
        }

        if ($user->default_tech_template_id === $this->template->id) {
            $user->update(['default_tech_template_id' => null]);
        }
    }
}
