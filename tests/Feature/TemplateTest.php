<?php

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
use App\Models\Project;
use App\Models\Template;
use App\Models\User;

describe('Template model', function () {
    it('can create a template with sections', function () {
        $user = User::factory()->create();

        $template = Template::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'document_type' => DocumentType::Prd,
            'sections' => [
                ['title' => 'Overview', 'description' => 'Test', 'required' => true],
            ],
        ]);

        expect($template->sections)->toBeArray()
            ->and($template->sections[0]['title'])->toBe('Overview');
    });

    it('casts document_type to enum', function () {
        $template = Template::factory()->prd()->create();

        expect($template->document_type)->toBe(DocumentType::Prd);
    });

    it('casts category to enum', function () {
        $template = Template::factory()
            ->inCategory(TemplateCategory::Core)
            ->create();

        expect($template->category)->toBe(TemplateCategory::Core);
    });

    it('formats sections for prompt injection', function () {
        $template = Template::factory()->create([
            'sections' => [
                ['title' => 'Overview', 'description' => 'Summary', 'required' => true],
                ['title' => 'Details', 'description' => 'More info', 'required' => false],
            ],
        ]);

        $formatted = $template->getFormattedSectionsForPrompt();

        expect($formatted)->toContain('Overview')
            ->and($formatted)->toContain('(Required)')
            ->and($formatted)->toContain('(Optional)');
    });

    it('includes guidance in formatted sections', function () {
        $template = Template::factory()->create([
            'sections' => [
                [
                    'title' => 'API Overview',
                    'description' => 'High-level summary',
                    'required' => true,
                    'guidance' => 'Explain what the API does',
                ],
            ],
        ]);

        $formatted = $template->getFormattedSectionsForPrompt();

        expect($formatted)->toContain('Guidance: Explain what the API does');
    });

    it('returns empty string for templates with no sections', function () {
        $template = Template::factory()->create([
            'sections' => [],
        ]);

        expect($template->getFormattedSectionsForPrompt())->toBe('');
    });

    it('increments usage count', function () {
        $template = Template::factory()->create(['usage_count' => 0]);

        $template->recordUsage();

        expect($template->fresh()->usage_count)->toBe(1);
    });
});

describe('Template queries', function () {
    it('gets available templates for document type', function () {
        Template::factory()->builtIn()->prd()->count(3)->create();
        Template::factory()->builtIn()->tech()->count(2)->create();

        $prdTemplates = Template::getAvailable(DocumentType::Prd);
        $techTemplates = Template::getAvailable(DocumentType::Tech);

        expect($prdTemplates)->toHaveCount(3)
            ->and($techTemplates)->toHaveCount(2);
    });

    it('includes user custom templates', function () {
        $user = User::factory()->create();
        Template::factory()->builtIn()->prd()->count(2)->create();
        Template::factory()->for($user)->prd()->create();

        $templates = Template::getAvailable(DocumentType::Prd, $user->id);

        expect($templates)->toHaveCount(3);
    });

    it('groups templates by category', function () {
        Template::factory()->builtIn()->prd()
            ->inCategory(TemplateCategory::Core)->create();
        Template::factory()->builtIn()->prd()
            ->inCategory(TemplateCategory::Strategy)->create();

        $grouped = Template::getGroupedByCategory(DocumentType::Prd);

        expect($grouped)->toHaveCount(2);
        expect($grouped['core']['category'])->toBe(TemplateCategory::Core);
        expect($grouped['strategy']['category'])->toBe(TemplateCategory::Strategy);
    });

    it('orders by sort_order and usage_count', function () {
        Template::factory()->builtIn()->prd()->create(['sort_order' => 2, 'name' => 'B']);
        Template::factory()->builtIn()->prd()->create(['sort_order' => 1, 'name' => 'A']);

        $templates = Template::getAvailable(DocumentType::Prd);

        expect($templates->first()->name)->toBe('A');
    });
});

describe('Project template relationships', function () {
    it('can set prd template on project', function () {
        $project = Project::factory()->create();
        $template = Template::factory()->prd()->create();

        $project->update(['prd_template_id' => $template->id]);

        expect($project->fresh()->prdTemplate->id)->toBe($template->id);
    });

    it('can set tech template on project', function () {
        $project = Project::factory()->create();
        $template = Template::factory()->tech()->create();

        $project->update(['tech_template_id' => $template->id]);

        expect($project->fresh()->techTemplate->id)->toBe($template->id);
    });

    it('nullifies template reference when template deleted', function () {
        $template = Template::factory()->prd()->create();
        $project = Project::factory()->create(['prd_template_id' => $template->id]);

        $template->delete();

        expect($project->fresh()->prd_template_id)->toBeNull();
    });
});
