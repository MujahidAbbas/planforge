<?php

use App\Enums\DocumentType;
use App\Enums\FeedbackType;
use App\Jobs\GenerateFeedbackJob;
use App\Livewire\Projects\Partials\FeedbackPanel;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
});

describe('FeedbackPanel component', function () {
    it('renders for document that exists', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->assertOk();
    });

    it('dispatches job when feedback requested', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('getFeedback', 'completeness')
            ->assertSet('isLoading', true)
            ->assertSet('showPanel', true);

        Queue::assertPushed(GenerateFeedbackJob::class, function ($job) use ($doc) {
            return $job->documentId === $doc->id
                && $job->feedbackType === FeedbackType::Completeness;
        });
    });

    it('uses cached feedback if available', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        $cacheKey = "feedback:{$this->project->id}:prd:{$version->id}:completeness";
        Cache::put($cacheKey, [
            'feedback' => '## Test Feedback',
            'type' => 'completeness',
            'generated_at' => now()->toIso8601String(),
        ], now()->addHour());

        Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('getFeedback', 'completeness')
            ->assertSet('isLoading', false)
            ->assertSee('Test Feedback');

        Queue::assertNotPushed(GenerateFeedbackJob::class);
    });

    it('can dismiss feedback panel', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('getFeedback', 'overall')
            ->assertSet('showPanel', true)
            ->call('dismissFeedback')
            ->assertSet('showPanel', false)
            ->assertSet('selectedType', null)
            ->assertSet('isLoading', false);
    });

    it('checks cache on poll', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        $component = Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('getFeedback', 'clarity')
            ->assertSet('isLoading', true);

        // Simulate job completion by adding to cache
        $cacheKey = "feedback:{$this->project->id}:prd:{$version->id}:clarity";
        Cache::put($cacheKey, [
            'feedback' => '## Clarity Feedback',
            'type' => 'clarity',
            'generated_at' => now()->toIso8601String(),
        ], now()->addHour());

        $component->call('checkFeedback')
            ->assertSet('isLoading', false);
    });

    it('does nothing when document does not exist', function () {
        Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ])
            ->call('getFeedback', 'completeness')
            ->assertSet('showPanel', false);

        Queue::assertNotPushed(GenerateFeedbackJob::class);
    });

    it('handles all feedback types', function () {
        $doc = Document::factory()->prd()->for($this->project)->create();
        $version = DocumentVersion::factory()
            ->for($doc, 'document')
            ->withContent('# PRD Content')
            ->create();
        $doc->update(['current_version_id' => $version->id]);

        $component = Livewire::actingAs($this->user)
            ->test(FeedbackPanel::class, [
                'projectId' => $this->project->id,
                'documentType' => 'prd',
            ]);

        expect($component->get('feedbackTypes'))->toHaveCount(5);

        foreach (FeedbackType::cases() as $type) {
            Queue::fake(); // Reset queue for each type

            $component->call('getFeedback', $type->value)
                ->assertSet('selectedType', $type->value);

            Queue::assertPushed(GenerateFeedbackJob::class);
        }
    });
});

describe('FeedbackType enum', function () {
    it('has label for each type', function () {
        expect(FeedbackType::Completeness->label())->toBe('Completeness');
        expect(FeedbackType::Clarity->label())->toBe('Clarity');
        expect(FeedbackType::Technical->label())->toBe('Technical Review');
        expect(FeedbackType::Stakeholder->label())->toBe('Stakeholder Ready');
        expect(FeedbackType::Overall->label())->toBe('Overall Assessment');
    });

    it('has description for each type', function () {
        foreach (FeedbackType::cases() as $type) {
            expect($type->description())->toBeString()->not->toBeEmpty();
        }
    });

    it('has icon for each type', function () {
        foreach (FeedbackType::cases() as $type) {
            expect($type->icon())->toBeString()->not->toBeEmpty();
        }
    });
});
