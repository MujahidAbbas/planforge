<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Livewire\Projects\Tabs\KanbanBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;

describe('KanbanBoard Actions', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    });

    describe('Create Action', function () {
        it('can create a new task in todo column', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'New Task',
                    'description' => 'Task description',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('tasks', [
                'project_id' => $this->project->id,
                'title' => 'New Task',
                'description' => 'Task description',
                'status' => TaskStatus::Todo->value,
            ]);
        });

        it('can create a new task in doing column', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'In Progress Task',
                    'description' => 'Working on this task',
                ], arguments: ['column' => 'doing'])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('tasks', [
                'project_id' => $this->project->id,
                'title' => 'In Progress Task',
                'status' => TaskStatus::Doing->value,
            ]);
        });

        it('can create a new task in done column', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Completed Task',
                    'description' => 'This task is done',
                ], arguments: ['column' => 'done'])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('tasks', [
                'project_id' => $this->project->id,
                'title' => 'Completed Task',
                'status' => TaskStatus::Done->value,
            ]);
        });

        it('validates required title field', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => '',
                    'description' => 'Some description',
                ], arguments: ['column' => 'todo'])
                ->assertHasActionErrors(['title' => 'required']);
        });

        it('validates title max length', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => str_repeat('a', 300),
                    'description' => 'Some description',
                ], arguments: ['column' => 'todo'])
                ->assertHasActionErrors(['title' => 'max']);
        });

        it('assigns correct position to new task', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Positioned Task',
                    'description' => 'Task with position',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $task = Task::where('title', 'Positioned Task')->first();

            expect($task->position)->not->toBeNull();
            expect((float) $task->position)->toBeGreaterThan(0);
        });

        it('assigns correct project_id to new task', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Project Task',
                    'description' => 'Task in project',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $task = Task::where('title', 'Project Task')->first();

            expect($task->project_id)->toBe($this->project->id);
        });

        it('creates task with description', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Task With Description',
                    'description' => 'Detailed description here',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('tasks', [
                'title' => 'Task With Description',
                'description' => 'Detailed description here',
            ]);
        });

        it('creates task with estimate', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Task With Estimate',
                    'description' => 'Task description',
                    'estimate' => '4h',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('tasks', [
                'title' => 'Task With Estimate',
                'estimate' => '4h',
            ]);
        });

        it('creates task with minimal fields', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'Minimal Task',
                    'description' => 'Required description',
                ], arguments: ['column' => 'todo'])
                ->assertHasNoActionErrors();

            $task = Task::where('title', 'Minimal Task')->first();

            expect($task)->not->toBeNull();
            expect($task->status->value)->toBe('todo');
        });
    });

    describe('Edit Action', function () {
        it('can edit task title', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'Original Title',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => 'Updated Title',
                    'description' => $task->description,
                    'estimate' => $task->estimate,
                    'status' => $task->status->value,
                ], arguments: ['recordKey' => $task->id])
                ->assertHasNoActionErrors();

            expect($task->fresh()->title)->toBe('Updated Title');
        });

        it('can edit task description', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'description' => 'Original description',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => $task->title,
                    'description' => 'Updated description',
                    'estimate' => $task->estimate,
                    'status' => $task->status->value,
                ], arguments: ['recordKey' => $task->id])
                ->assertHasNoActionErrors();

            expect($task->fresh()->description)->toBe('Updated description');
        });

        it('can edit task estimate', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'estimate' => '2h',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => $task->title,
                    'description' => $task->description,
                    'estimate' => '8h',
                    'status' => $task->status->value,
                ], arguments: ['recordKey' => $task->id])
                ->assertHasNoActionErrors();

            expect($task->fresh()->estimate)->toBe('8h');
        });

        it('can change task status via edit form', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => $task->title,
                    'description' => $task->description,
                    'estimate' => $task->estimate,
                    'status' => 'done',
                ], arguments: ['recordKey' => $task->id])
                ->assertHasNoActionErrors();

            expect($task->fresh()->status)->toBe(TaskStatus::Done);
        });

        it('validates required title on edit', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => '',
                    'description' => $task->description,
                    'status' => $task->status->value,
                ], arguments: ['recordKey' => $task->id])
                ->assertHasActionErrors(['title' => 'required']);
        });

        it('preserves task position on edit', function () {
            $position = DecimalPosition::forEmptyColumn();
            $task = Task::factory()->withPosition($position)->create([
                'project_id' => $this->project->id,
                'title' => 'Original Title',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('edit', [
                    'title' => 'Updated Title',
                    'description' => $task->description,
                    'estimate' => $task->estimate,
                    'status' => $task->status->value,
                ], arguments: ['recordKey' => $task->id])
                ->assertHasNoActionErrors();

            // Compare float values since DB may normalize the position format
            expect((float) $task->fresh()->position)->toBe((float) $position);
        });
    });

    describe('Delete Action', function () {
        it('can delete a task via model', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'Task to Delete',
            ]);

            // Verify task is displayed
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Task to Delete');

            // Delete task directly (simulating delete action behavior)
            $task->delete();

            // Task should be soft deleted
            $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        });

        it('soft deletes the task', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
            ]);

            // Delete via model (Flowforge card actions use DeleteAction internally)
            $task->delete();

            // Task still exists in DB but with deleted_at
            expect(Task::withTrashed()->find($task->id))->not->toBeNull();
            expect(Task::withTrashed()->find($task->id)->deleted_at)->not->toBeNull();
        });

        it('hides deleted task from board', function () {
            $task = Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'Task to Remove',
            ]);

            // Verify task is displayed first
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Task to Remove');

            // Delete the task
            $task->delete();

            // Re-render the component to verify task is gone
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertDontSee('Task to Remove');
        });
    });
});
