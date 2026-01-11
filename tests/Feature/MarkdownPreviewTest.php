<?php

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('PRD Markdown Preview', function () {
    beforeEach(function () {
        $this->document = Document::factory()
            ->prd()
            ->for($this->project)
            ->create();

        $this->version = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Test PRD\n\nThis is a **bold** test.')
            ->create(['created_by' => $this->user->id]);

        $this->document->update(['current_version_id' => $this->version->id]);
    });

    it('initializes with write mode by default', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component->assertSet('editorMode', 'write');
    });

    it('can switch to preview mode', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('setEditorMode', 'preview')
            ->assertSet('editorMode', 'preview');
    });

    it('can switch to split mode', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('setEditorMode', 'split')
            ->assertSet('editorMode', 'split');
    });

    it('rejects invalid modes', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('setEditorMode', 'invalid')
            ->assertSet('editorMode', 'write');
    });

    it('computes preview HTML from content', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component->set('content', '# Hello World');

        $instance = $component->instance();
        $previewHtml = $instance->previewHtml;

        expect($previewHtml)->toContain('<h1>Hello World</h1>');
    });

    it('clears preview cache when content changes', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component->set('content', '# First');
        $instance = $component->instance();
        $firstHtml = $instance->previewHtml;
        expect($firstHtml)->toContain('<h1>First</h1>');

        $component->set('content', '# Second');
        $instance = $component->instance();
        $secondHtml = $instance->previewHtml;
        expect($secondHtml)->toContain('<h1>Second</h1>');
    });

    it('returns empty string for empty content', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component->set('content', '');

        $instance = $component->instance();
        expect($instance->previewHtml)->toBe('');
    });
});

describe('Tech Spec Markdown Preview', function () {
    beforeEach(function () {
        $this->document = Document::factory()
            ->tech()
            ->for($this->project)
            ->create();

        $this->version = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Tech Spec\n\n## Architecture')
            ->create(['created_by' => $this->user->id]);

        $this->document->update(['current_version_id' => $this->version->id]);
    });

    it('has the same markdown preview functionality as PRD', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Tech::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->assertSet('editorMode', 'write')
            ->call('setEditorMode', 'preview')
            ->assertSet('editorMode', 'preview')
            ->call('setEditorMode', 'split')
            ->assertSet('editorMode', 'split');
    });

    it('computes preview HTML correctly', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Tech::class, [
            'projectId' => $this->project->id,
        ]);

        $markdown = <<<'MD'
## Architecture

- API Gateway
- Database
MD;

        $component->set('content', $markdown);

        $instance = $component->instance();
        $previewHtml = $instance->previewHtml;

        expect($previewHtml)->toContain('<h2>Architecture</h2>');
        expect($previewHtml)->toContain('<li>API Gateway</li>');
    });
});
