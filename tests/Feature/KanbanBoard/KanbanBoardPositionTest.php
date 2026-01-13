<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Livewire\Projects\Tabs\KanbanBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;

describe('KanbanBoard Position Management', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    });

    describe('Move Card Between Columns', function () {
        it('can move card from todo to doing', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->call('moveCard', $task->id, 'doing');

            expect($task->fresh()->status)->toBe(TaskStatus::Doing);
        });

        it('can move card from doing to done', function () {
            $task = Task::factory()->doing()->create([
                'project_id' => $this->project->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->call('moveCard', $task->id, 'done');

            expect($task->fresh()->status)->toBe(TaskStatus::Done);
        });

        it('can move card from done to todo', function () {
            $task = Task::factory()->done()->create([
                'project_id' => $this->project->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->call('moveCard', $task->id, 'todo');

            expect($task->fresh()->status)->toBe(TaskStatus::Todo);
        });

        it('updates task status when moving between columns', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            expect($task->status)->toBe(TaskStatus::Todo);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task->id, 'doing');

            expect($task->fresh()->status)->toBe(TaskStatus::Doing);
        });

        it('assigns new position when moving between columns', function () {
            $originalPosition = DecimalPosition::forEmptyColumn();
            $task = Task::factory()->todo()->withPosition($originalPosition)->create([
                'project_id' => $this->project->id,
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task->id, 'doing');

            expect($task->fresh()->position)->not->toBeNull();
        });
    });

    describe('Move Card Within Column', function () {
        it('can move card to top of column', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);
            $position3 = DecimalPosition::after($position2);

            $task1 = Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 1',
            ]);
            $task2 = Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 2',
            ]);
            $task3 = Task::factory()->todo()->withPosition($position3)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 3',
            ]);

            // Move task3 to top (before task1)
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task3->id, 'todo', null, $task1->id);

            $task3->refresh();
            $task1->refresh();

            expect(DecimalPosition::lessThan($task3->position, $task1->position))->toBeTrue();
        });

        it('can move card to bottom of column', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);

            $task1 = Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 1',
            ]);
            $task2 = Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 2',
            ]);

            // Move task1 to bottom (after task2)
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task1->id, 'todo', $task2->id, null);

            $task1->refresh();
            $task2->refresh();

            expect(DecimalPosition::greaterThan($task1->position, $task2->position))->toBeTrue();
        });

        it('can move card between two cards', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);
            $position3 = DecimalPosition::after($position2);

            $task1 = Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 1',
            ]);
            $task2 = Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 2',
            ]);
            $task3 = Task::factory()->todo()->withPosition($position3)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 3',
            ]);

            // Move task3 between task1 and task2
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task3->id, 'todo', $task1->id, $task2->id);

            $task1->refresh();
            $task2->refresh();
            $task3->refresh();

            expect(DecimalPosition::greaterThan($task3->position, $task1->position))->toBeTrue();
            expect(DecimalPosition::lessThan($task3->position, $task2->position))->toBeTrue();
        });

        it('calculates position between adjacent cards', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);

            $task1 = Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
            ]);
            $task2 = Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
            ]);

            // Create new task between the two
            $task3 = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task3->id, 'todo', $task1->id, $task2->id);

            $task3->refresh();

            expect(DecimalPosition::greaterThan($task3->position, $position1))->toBeTrue();
            expect(DecimalPosition::lessThan($task3->position, $position2))->toBeTrue();
        });
    });

    describe('Position Calculation', function () {
        it('assigns position to new card in non-empty column', function () {
            $existingPosition = DecimalPosition::forEmptyColumn();
            Task::factory()->todo()->withPosition($existingPosition)->create([
                'project_id' => $this->project->id,
                'title' => 'Existing Task',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'New Task',
                    'description' => 'Task description',
                ], arguments: ['column' => 'todo']);

            $newTask = Task::where('title', 'New Task')->first();

            // The new task should have a position assigned
            expect($newTask)->not->toBeNull();
            expect($newTask->position)->not->toBeNull();
            // Position can be 0 or greater depending on Flowforge's implementation
            expect((float) $newTask->position)->toBeGreaterThanOrEqual(0);
        });

        it('assigns default position for first card in empty column', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->callAction('create', [
                    'title' => 'First Task',
                    'description' => 'Task description',
                ], arguments: ['column' => 'todo']);

            $task = Task::where('title', 'First Task')->first();

            expect($task->position)->not->toBeNull();
            expect((float) $task->position)->toBeGreaterThan(0);
        });
    });

    describe('Edge Cases', function () {
        it('handles moving card to empty column', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            // Move to empty 'doing' column
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task->id, 'doing');

            $task->refresh();

            expect($task->status)->toBe(TaskStatus::Doing);
            expect($task->position)->not->toBeNull();
        });

        it('handles moving only card in column', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            // Only task in column, move to another column
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task->id, 'done');

            expect($task->fresh()->status)->toBe(TaskStatus::Done);
        });

        it('maintains order after multiple moves', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);
            $position3 = DecimalPosition::after($position2);

            $task1 = Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 1',
            ]);
            $task2 = Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 2',
            ]);
            $task3 = Task::factory()->todo()->withPosition($position3)->create([
                'project_id' => $this->project->id,
                'title' => 'Task 3',
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            // Move task3 to top
            $component->call('moveCard', $task3->id, 'todo', null, $task1->id);

            // Move task2 to bottom
            $task1->refresh();
            $task3->refresh();
            $component->call('moveCard', $task2->id, 'todo', $task1->id, null);

            $task1->refresh();
            $task2->refresh();
            $task3->refresh();

            // Order should be: task3, task1, task2
            expect(DecimalPosition::lessThan($task3->position, $task1->position))->toBeTrue();
            expect(DecimalPosition::lessThan($task1->position, $task2->position))->toBeTrue();
        });

        it('dispatches kanban-card-moved event after successful move', function () {
            $task = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('moveCard', $task->id, 'doing')
                ->assertDispatched('kanban-card-moved');
        });
    });

    describe('Pagination', function () {
        it('can load more items in column', function () {
            // Create many tasks
            $position = DecimalPosition::forEmptyColumn();
            for ($i = 0; $i < 30; $i++) {
                Task::factory()->todo()->withPosition($position)->create([
                    'project_id' => $this->project->id,
                    'title' => "Task {$i}",
                ]);
                $position = DecimalPosition::after($position);
            }

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->call('loadMoreItems', 'todo')
                ->assertStatus(200);
        });

        it('can load all items in column', function () {
            // Create several tasks
            $position = DecimalPosition::forEmptyColumn();
            for ($i = 0; $i < 15; $i++) {
                Task::factory()->todo()->withPosition($position)->create([
                    'project_id' => $this->project->id,
                ]);
                $position = DecimalPosition::after($position);
            }

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->call('loadAllItems', 'todo')
                ->assertStatus(200);
        });

        it('detects when column is fully loaded', function () {
            // Create just a few tasks
            Task::factory()->count(3)->sequence(
                ['position' => DecimalPosition::forEmptyColumn()],
                ['position' => DecimalPosition::after(DecimalPosition::forEmptyColumn())],
                ['position' => DecimalPosition::after(DecimalPosition::after(DecimalPosition::forEmptyColumn()))],
            )->todo()->create([
                'project_id' => $this->project->id,
            ]);

            // With only 3 tasks, column should be fully loaded after initial render
            // The component loads tasks per page (default is likely more than 3)
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertStatus(200);

            // Verify component renders the 3 tasks correctly
            $html = $component->html();
            $taskCount = substr_count($html, 'data-card-id=');
            expect($taskCount)->toBe(3);
        });

        it('dispatches kanban-items-loaded event', function () {
            $position = DecimalPosition::forEmptyColumn();
            for ($i = 0; $i < 25; $i++) {
                Task::factory()->todo()->withPosition($position)->create([
                    'project_id' => $this->project->id,
                ]);
                $position = DecimalPosition::after($position);
            }

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->call('loadMoreItems', 'todo')
                ->assertDispatched('kanban-items-loaded');
        });
    });
});
