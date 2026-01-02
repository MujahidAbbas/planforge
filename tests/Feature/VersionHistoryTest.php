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

describe('PRD Version History', function () {
    beforeEach(function () {
        $this->document = Document::factory()
            ->prd()
            ->for($this->project)
            ->create();

        $this->version1 = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Version 1 Content')
            ->create(['created_by' => $this->user->id]);

        $this->version2 = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Version 2 Content')
            ->create(['created_by' => $this->user->id]);

        $this->document->update(['current_version_id' => $this->version2->id]);
    });

    it('loads versions for the document', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component->assertSet('showVersionHistory', false);

        // Access computed property through the instance
        $instance = $component->instance();
        $versions = $instance->versions;

        expect($versions)->toHaveCount(2);
        expect($versions->first()->id)->toBe($this->version2->id);
    });

    it('opens and closes version history panel', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->assertSet('showVersionHistory', false)
            ->call('openVersionHistory')
            ->assertSet('showVersionHistory', true)
            ->call('closeVersionHistory')
            ->assertSet('showVersionHistory', false);
    });

    it('selects a version for preview', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('openVersionHistory')
            ->call('selectVersion', $this->version1->id)
            ->assertSet('previewVersionId', $this->version1->id);

        $instance = $component->instance();
        $selectedVersion = $instance->selectedVersionForPreview;
        expect($selectedVersion->id)->toBe($this->version1->id);
        expect($selectedVersion->content_md)->toBe('# Version 1 Content');
    });

    it('restores an old version by creating a new version', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('openVersionHistory')
            ->call('restoreVersion', $this->version1->id);

        // Should have 3 versions now (original 2 + restored)
        $this->document->refresh();
        expect($this->document->versions()->count())->toBe(3);

        // Current version should be the new one
        $currentVersion = $this->document->currentVersion;
        expect($currentVersion->content_md)->toBe('# Version 1 Content');
        expect($currentVersion->summary)->toContain('Restored from');

        // Panel should be closed
        $component->assertSet('showVersionHistory', false);
    });

    it('dispatches events after restore', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('restoreVersion', $this->version1->id)
            ->assertDispatched('docUpdated', type: 'prd')
            ->assertDispatched('version-restored');
    });
});

describe('Tech Spec Version History', function () {
    beforeEach(function () {
        $this->document = Document::factory()
            ->tech()
            ->for($this->project)
            ->create();

        $this->version1 = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Tech Version 1')
            ->create(['created_by' => $this->user->id]);

        $this->document->update(['current_version_id' => $this->version1->id]);
    });

    it('works the same as PRD version history', function () {
        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Tech::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->assertSet('showVersionHistory', false)
            ->call('openVersionHistory')
            ->assertSet('showVersionHistory', true);

        $instance = $component->instance();
        $versions = $instance->versions;
        expect($versions)->toHaveCount(1);
    });

    it('dispatches tech type in events', function () {
        actingAs($this->user);

        // Create a second version to restore from
        $version2 = DocumentVersion::factory()
            ->for($this->document, 'document')
            ->withContent('# Tech Version 2')
            ->create(['created_by' => $this->user->id]);

        $this->document->update(['current_version_id' => $version2->id]);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Tech::class, [
            'projectId' => $this->project->id,
        ]);

        $component
            ->call('restoreVersion', $this->version1->id)
            ->assertDispatched('docUpdated', type: 'tech');
    });
});

describe('Version History Security', function () {
    it('cannot restore version from different document', function () {
        // Create a different project with its own PRD
        $otherProject = Project::factory()->for($this->user)->create();
        $otherDocument = Document::factory()
            ->prd()
            ->for($otherProject)
            ->create();

        $otherVersion = DocumentVersion::factory()
            ->for($otherDocument, 'document')
            ->withContent('# Other Document Content')
            ->create();

        // Create PRD for our project
        $document = Document::factory()
            ->prd()
            ->for($this->project)
            ->create();

        $version = DocumentVersion::factory()
            ->for($document, 'document')
            ->withContent('# My Content')
            ->create();

        $document->update(['current_version_id' => $version->id]);

        actingAs($this->user);

        $component = Livewire\Livewire::test(\App\Livewire\Projects\Tabs\Prd::class, [
            'projectId' => $this->project->id,
        ]);

        // Try to restore version from different document - should not work
        $component->call('restoreVersion', $otherVersion->id);

        // Document should still only have 1 version
        expect($document->versions()->count())->toBe(1);
    });
});
