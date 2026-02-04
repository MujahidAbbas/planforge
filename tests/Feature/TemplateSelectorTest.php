<?php

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

    it('shows template info when selected', function () {
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
            ->assertSee('1 section'); // Section count displayed instead of section titles
    });
});

describe('TemplateSelector search functionality', function () {
    it('filters templates by search query', function () {
        Template::factory()->builtIn()->prd()->create(['name' => 'Alpha Template']);
        Template::factory()->builtIn()->prd()->create(['name' => 'Beta Template']);
        Template::factory()->builtIn()->prd()->create(['name' => 'Gamma Template']);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->set('search', 'Alpha');

        $builtInTemplates = $component->get('builtInGroupedTemplates');
        $allTemplates = $builtInTemplates->flatMap(fn ($group) => $group['templates']);

        expect($allTemplates)->toHaveCount(1);
        expect($allTemplates->first()->name)->toBe('Alpha Template');
    });

    it('filters templates by description', function () {
        Template::factory()->builtIn()->prd()->create([
            'name' => 'Template One',
            'description' => 'For mobile applications',
        ]);
        Template::factory()->builtIn()->prd()->create([
            'name' => 'Template Two',
            'description' => 'For web applications',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->set('search', 'mobile');

        $builtInTemplates = $component->get('builtInGroupedTemplates');
        $allTemplates = $builtInTemplates->flatMap(fn ($group) => $group['templates']);

        expect($allTemplates)->toHaveCount(1);
        expect($allTemplates->first()->name)->toBe('Template One');
    });

    it('shows no results message when search has no matches', function () {
        Template::factory()->builtIn()->prd()->create(['name' => 'Test Template']);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->set('search', 'nonexistent')
            ->assertSet('hasSearchResults', false);
    });

    it('can clear search query', function () {
        Template::factory()->builtIn()->prd()->count(3)->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->set('search', 'test')
            ->call('clearSearch')
            ->assertSet('search', '');
    });

    it('filters user templates by search', function () {
        Template::factory()->for($this->user)->prd()->create(['name' => 'My Custom Alpha']);
        Template::factory()->for($this->user)->prd()->create(['name' => 'My Custom Beta']);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->set('search', 'Alpha');

        expect($component->get('myTemplates'))->toHaveCount(1);
        expect($component->get('myTemplates')->first()->name)->toBe('My Custom Alpha');
    });
});

describe('TemplateSelector preview functionality', function () {
    it('can open preview for a template', function () {
        $template = Template::factory()->builtIn()->prd()->create([
            'name' => 'Preview Test Template',
            'sections' => [
                ['title' => 'Overview', 'description' => 'Test', 'required' => true],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('preview', $template->id)
            ->assertSet('showPreview', true)
            ->assertSet('previewTemplateId', $template->id);
    });

    it('can close preview', function () {
        $template = Template::factory()->builtIn()->prd()->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('preview', $template->id)
            ->assertSet('showPreview', true)
            ->call('closePreview')
            ->assertSet('showPreview', false)
            ->assertSet('previewTemplateId', null);
    });

    it('can select template from preview', function () {
        $template = Template::factory()->builtIn()->prd()->create();

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('preview', $template->id)
            ->call('selectFromPreview')
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('showPreview', false)
            ->assertDispatched('template-selected');

        expect($this->project->fresh()->prd_template_id)->toBe($template->id);
    });

    it('returns preview template computed property', function () {
        $template = Template::factory()->builtIn()->prd()->create([
            'name' => 'Computed Preview Template',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('preview', $template->id);

        expect($component->get('previewTemplate')->name)->toBe('Computed Preview Template');
    });
});

describe('TemplateSelector recommended template', function () {
    it('returns recommended template for PRD', function () {
        $recommended = Template::factory()->builtIn()->prd()->create([
            'name' => 'PlanForge PRD',
        ]);
        Template::factory()->builtIn()->prd()->create(['name' => 'Other Template']);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ]);

        expect($component->get('recommendedTemplate')->id)->toBe($recommended->id);
    });

    it('returns recommended template for tech', function () {
        $recommended = Template::factory()->builtIn()->tech()->create([
            'name' => 'PlanForge Tech Spec',
        ]);
        Template::factory()->builtIn()->tech()->create(['name' => 'Other Tech Template']);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'tech',
            ]);

        expect($component->get('recommendedTemplate')->id)->toBe($recommended->id);
    });
});

describe('TemplateSelector quick options', function () {
    it('returns quick options including No Template', function () {
        Template::factory()->builtIn()->prd()->create([
            'name' => 'No Template',
            'category' => TemplateCategory::Core,
        ]);
        Template::factory()->builtIn()->prd()->create([
            'name' => 'PlanForge PRD',
            'category' => TemplateCategory::Core,
        ]);
        Template::factory()->builtIn()->prd()->create([
            'name' => 'Minimal PRD',
            'category' => TemplateCategory::Core,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ]);

        $quickOptions = $component->get('quickOptions');

        expect($quickOptions)->toHaveCount(2);
        expect(collect($quickOptions)->pluck('name'))->toContain('No Template');
    });
});

describe('TemplateSelector dispatches template details', function () {
    it('dispatches template name and section count on selection', function () {
        $template = Template::factory()->builtIn()->prd()->create([
            'name' => 'Detail Test Template',
            'sections' => [
                ['title' => 'Section 1', 'description' => '', 'required' => true],
                ['title' => 'Section 2', 'description' => '', 'required' => false],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(TemplateSelector::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('selectTemplate', $template->id)
            ->assertDispatched('template-selected', function ($name, $params) use ($template) {
                return $params['templateId'] === $template->id
                    && $params['templateName'] === 'Detail Test Template'
                    && $params['sectionCount'] === 2;
            });
    });
});
