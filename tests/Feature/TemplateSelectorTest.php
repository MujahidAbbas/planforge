<?php

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
use App\Livewire\Projects\Partials\TemplateSelector;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('TemplateSelector component', function () {
    it('shows available templates', function () {
        Template::factory()->builtIn()->prd()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->assertOk();
    });

    it('can select a template', function () {
        $template = Template::factory()->builtIn()->prd()->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('selectTemplate', $template->id)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertDispatched('template-selected');

        expect($this->project->fresh()->prd_template_id)->toBe($template->id);
    });

    it('can clear template selection', function () {
        $template = Template::factory()->builtIn()->prd()->create();
        $this->project->update(['prd_template_id' => $template->id]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->assertSet('selectedTemplateId', $template->id)
            ->call('clearTemplate')
            ->assertSet('selectedTemplateId', null)
            ->assertDispatched('template-cleared');

        expect($this->project->fresh()->prd_template_id)->toBeNull();
    });

    it('loads current selection on mount', function () {
        $template = Template::factory()->builtIn()->prd()->create();
        $this->project->update(['prd_template_id' => $template->id]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->assertSet('selectedTemplateId', $template->id);
    });

    it('groups templates by category', function () {
        Template::factory()->builtIn()->prd()
            ->inCategory(TemplateCategory::Core)->create();
        Template::factory()->builtIn()->prd()
            ->inCategory(TemplateCategory::Strategy)->create();

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ]);

        expect($component->get('groupedTemplates'))->toHaveCount(2);
    });

    it('handles tech document type', function () {
        $template = Template::factory()->builtIn()->tech()->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'tech',
            ])
            ->call('selectTemplate', $template->id);

        expect($this->project->fresh()->tech_template_id)->toBe($template->id);
    });

    it('shows template preview when selected', function () {
        $template = Template::factory()->builtIn()->prd()->create([
            'name' => 'Test PRD Template',
            'sections' => [
                ['title' => 'Overview', 'description' => 'Summary', 'required' => true],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('selectTemplate', $template->id)
            ->assertSee('Test PRD Template')
            ->assertSee('Overview');
    });
});
