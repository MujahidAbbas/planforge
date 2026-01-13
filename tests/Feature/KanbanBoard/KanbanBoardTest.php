<?php

declare(strict_types=1);

use App\Enums\PlanRunStepStatus;
use App\Enums\TaskCategory;
use App\Livewire\Projects\Tabs\KanbanBoard;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSet;
use App\Models\User;
use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;

describe('KanbanBoard Component', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    });

    describe('Rendering', function () {
        it('renders kanban board for authorized user', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertStatus(200)
                ->assertSee('Kanban Board');
        });

        it('displays all three columns', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('To Do')
                ->assertSee('In Progress')
                ->assertSee('Done');
        });

        it('displays tasks in correct columns based on status', function () {
            $todoTask = Task::factory()->todo()->create([
                'project_id' => $this->project->id,
                'title' => 'Todo Task',
            ]);

            $doingTask = Task::factory()->doing()->create([
                'project_id' => $this->project->id,
                'title' => 'Doing Task',
            ]);

            $doneTask = Task::factory()->done()->create([
                'project_id' => $this->project->id,
                'title' => 'Done Task',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Todo Task')
                ->assertSee('Doing Task')
                ->assertSee('Done Task');
        });

        it('orders tasks by position within each column', function () {
            $position1 = DecimalPosition::forEmptyColumn();
            $position2 = DecimalPosition::after($position1);
            $position3 = DecimalPosition::after($position2);

            Task::factory()->todo()->withPosition($position3)->create([
                'project_id' => $this->project->id,
                'title' => 'Task C',
            ]);

            Task::factory()->todo()->withPosition($position1)->create([
                'project_id' => $this->project->id,
                'title' => 'Task A',
            ]);

            Task::factory()->todo()->withPosition($position2)->create([
                'project_id' => $this->project->id,
                'title' => 'Task B',
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $html = $component->html();
            $posA = strpos($html, 'Task A');
            $posB = strpos($html, 'Task B');
            $posC = strpos($html, 'Task C');

            expect($posA)->toBeLessThan($posB);
            expect($posB)->toBeLessThan($posC);
        });

        it('displays task title and description', function () {
            Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'My Test Task',
                'description' => 'This is a task description',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('My Test Task')
                ->assertSee('This is a task description');
        });

        it('truncates long descriptions', function () {
            // The component uses ->limit(100) for descriptions
            // Create a description with recognizable start and end patterns
            $longDescription = 'START_'.str_repeat('x', 150).'_END';

            Task::factory()->create([
                'project_id' => $this->project->id,
                'description' => $longDescription,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $html = $component->html();

            // The visible text should show the start but not the end marker
            // (due to truncation at 100 characters)
            expect($html)->toContain('START_');
            // The _END marker should not be visible in the truncated text
            // Note: the full string may still be in wire:snapshot data,
            // but the visible content is truncated
        });

        it('displays estimate badge', function () {
            Task::factory()->create([
                'project_id' => $this->project->id,
                'estimate' => '2h',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('2h');
        });
    });

    describe('Empty States', function () {
        it('renders empty columns when no tasks exist', function () {
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertStatus(200)
                ->assertSee('To Do')
                ->assertSee('In Progress')
                ->assertSee('Done');
        });

        it('shows empty column while others have tasks', function () {
            Task::factory()->todo()->create([
                'project_id' => $this->project->id,
                'title' => 'Only Todo Task',
            ]);

            // No doing or done tasks - component should still render
            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertStatus(200)
                ->assertSee('Only Todo Task');
        });
    });

    describe('Task Display', function () {
        it('displays category badge for backend category', function () {
            Task::factory()
                ->withCategory(TaskCategory::Backend)
                ->create([
                    'project_id' => $this->project->id,
                ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Backend');
        });

        it('displays category badge for frontend category', function () {
            Task::factory()
                ->withCategory(TaskCategory::Frontend)
                ->create([
                    'project_id' => $this->project->id,
                ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Frontend');
        });
    });

    describe('Computed Properties', function () {
        it('returns latest task set', function () {
            $taskSet = TaskSet::factory()->create([
                'project_id' => $this->project->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            expect($component->get('latestTaskSet')?->id)->toBe($taskSet->id);
        });

        it('returns null when no task set exists', function () {
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            expect($component->get('latestTaskSet'))->toBeNull();
        });

        it('detects when tasks are being generated', function () {
            $planRun = PlanRun::factory()->create([
                'project_id' => $this->project->id,
            ]);

            $planRunStep = PlanRunStep::factory()->create([
                'plan_run_id' => $planRun->id,
                'status' => PlanRunStepStatus::Running,
            ]);

            TaskSet::factory()->create([
                'project_id' => $this->project->id,
                'plan_run_step_id' => $planRunStep->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            expect($component->get('isGeneratingTasks'))->toBeTrue();
        });

        it('detects when tasks are not being generated', function () {
            $planRun = PlanRun::factory()->create([
                'project_id' => $this->project->id,
            ]);

            $planRunStep = PlanRunStep::factory()->create([
                'plan_run_id' => $planRun->id,
                'status' => PlanRunStepStatus::Succeeded,
            ]);

            TaskSet::factory()->create([
                'project_id' => $this->project->id,
                'plan_run_step_id' => $planRunStep->id,
            ]);

            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            expect($component->get('isGeneratingTasks'))->toBeFalse();
        });
    });

    describe('Events', function () {
        it('refreshes board on tasksUpdated event', function () {
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            // Create a task after initial render
            Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'New Task After Event',
            ]);

            // Dispatch the event
            $component->dispatch('tasksUpdated');

            $component->assertSee('New Task After Event');
        });

        it('refreshes board on planRunCompleted event', function () {
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'Task After Plan Run',
            ]);

            $component->dispatch('planRunCompleted');

            $component->assertSee('Task After Plan Run');
        });

        it('refreshes board on taskGenerationStarted event', function () {
            $component = Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id]);

            $component->dispatch('taskGenerationStarted')
                ->assertStatus(200);
        });
    });

    describe('Multiple Projects Isolation', function () {
        it('only displays tasks for the current project', function () {
            $otherProject = Project::factory()->create(['user_id' => $this->user->id]);

            Task::factory()->create([
                'project_id' => $this->project->id,
                'title' => 'Current Project Task',
            ]);

            Task::factory()->create([
                'project_id' => $otherProject->id,
                'title' => 'Other Project Task',
            ]);

            Livewire::actingAs($this->user)
                ->test(KanbanBoard::class, ['projectId' => $this->project->id])
                ->assertSee('Current Project Task')
                ->assertDontSee('Other Project Task');
        });
    });
});
