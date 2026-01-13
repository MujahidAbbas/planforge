<?php

declare(strict_types=1);

use App\Livewire\Templates\Create;
use App\Models\User;
use Livewire\Livewire;

test('sections can be reordered using wire:sort', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->call('selectMethod', 'manual')
        ->assertSet('currentStep', 2)
        ->assertSet('mode', 'manual');

    // Initial section is created with index 0
    $component->assertCount('sections', 1);

    // Add two more sections
    $component->call('addSection');
    $component->call('addSection');
    $component->assertCount('sections', 3);

    // Set titles to identify sections
    $component->set('sections.0.title', 'Section A');
    $component->set('sections.1.title', 'Section B');
    $component->set('sections.2.title', 'Section C');

    // Verify initial order
    expect($component->get('sections.0.title'))->toBe('Section A');
    expect($component->get('sections.1.title'))->toBe('Section B');
    expect($component->get('sections.2.title'))->toBe('Section C');

    // Move Section C (index 2) to position 0 (first)
    $component->call('reorderSection', 2, 0); // oldIndex=2, newIndex=0

    // Verify new order: C, A, B
    expect($component->get('sections.0.title'))->toBe('Section C');
    expect($component->get('sections.1.title'))->toBe('Section A');
    expect($component->get('sections.2.title'))->toBe('Section B');
});

test('sections can be moved from first to last position', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->call('selectMethod', 'manual');

    // Add two more sections (total 3)
    $component->call('addSection');
    $component->call('addSection');

    // Set titles
    $component->set('sections.0.title', 'First');
    $component->set('sections.1.title', 'Middle');
    $component->set('sections.2.title', 'Last');

    // Move First (index 0) to position 2 (last)
    $component->call('reorderSection', 0, 2);

    // Verify new order: Middle, Last, First
    expect($component->get('sections.0.title'))->toBe('Middle');
    expect($component->get('sections.1.title'))->toBe('Last');
    expect($component->get('sections.2.title'))->toBe('First');
});

test('sections can be moved from middle to first', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->call('selectMethod', 'manual');

    // Add two more sections (total 3)
    $component->call('addSection');
    $component->call('addSection');

    // Set titles
    $component->set('sections.0.title', 'First');
    $component->set('sections.1.title', 'Middle');
    $component->set('sections.2.title', 'Last');

    // Move Middle (index 1) to position 0 (first)
    $component->call('reorderSection', 1, 0);

    // Verify new order: Middle, First, Last
    expect($component->get('sections.0.title'))->toBe('Middle');
    expect($component->get('sections.1.title'))->toBe('First');
    expect($component->get('sections.2.title'))->toBe('Last');
});

test('reorder handles invalid item index gracefully', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->call('selectMethod', 'manual');

    // Only one section exists
    $component->set('sections.0.title', 'Only Section');

    // Try to move non-existent section (index 5)
    $component->call('reorderSection', 5, 0);

    // Should not change anything
    $component->assertCount('sections', 1);
    expect($component->get('sections.0.title'))->toBe('Only Section');
});

test('reorder works with string item parameter', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Create::class)
        ->call('selectMethod', 'manual');

    // Add another section
    $component->call('addSection');

    // Set titles
    $component->set('sections.0.title', 'First');
    $component->set('sections.1.title', 'Second');

    // Call with string index (as Livewire might pass it)
    $component->call('reorderSection', '1', 0);

    // Verify swap worked
    expect($component->get('sections.0.title'))->toBe('Second');
    expect($component->get('sections.1.title'))->toBe('First');
});
