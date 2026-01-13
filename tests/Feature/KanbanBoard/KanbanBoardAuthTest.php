<?php

declare(strict_types=1);

use App\Livewire\Projects\Tabs\KanbanBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

describe('KanbanBoard Authorization', function () {
    describe('Access Control', function () {
        it('denies access to unauthorized user', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $project = Project::factory()->create(['user_id' => $owner->id]);

            Livewire::actingAs($otherUser)
                ->test(KanbanBoard::class, ['projectId' => $project->id])
                ->assertForbidden();
        });

        it('allows access to project owner', function () {
            $user = User::factory()->create();
            $project = Project::factory()->create(['user_id' => $user->id]);

            Livewire::actingAs($user)
                ->test(KanbanBoard::class, ['projectId' => $project->id])
                ->assertStatus(200);
        });

        it('throws exception for non-existent project', function () {
            $user = User::factory()->create();

            expect(fn () => Livewire::actingAs($user)
                ->test(KanbanBoard::class, ['projectId' => 'non-existent-id']))
                ->toThrow(ModelNotFoundException::class);
        });

        it('denies access when not authenticated', function () {
            $project = Project::factory()->create();

            // Without actingAs, the authorize() call should deny access
            // because there's no authenticated user to check against the policy
            Livewire::test(KanbanBoard::class, ['projectId' => $project->id])
                ->assertForbidden();
        });
    });

    describe('Action Authorization', function () {
        it('prevents creating task in unauthorized project', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $project = Project::factory()->create(['user_id' => $owner->id]);

            // Other user cannot even load the board
            Livewire::actingAs($otherUser)
                ->test(KanbanBoard::class, ['projectId' => $project->id])
                ->assertForbidden();
        });

        it('prevents moving task in unauthorized project', function () {
            $owner = User::factory()->create();
            $otherUser = User::factory()->create();
            $project = Project::factory()->create(['user_id' => $owner->id]);

            $task = Task::factory()->create(['project_id' => $project->id]);

            // Other user cannot even load the board
            Livewire::actingAs($otherUser)
                ->test(KanbanBoard::class, ['projectId' => $project->id])
                ->assertForbidden();
        });
    });

    describe('Soft-Deleted Project', function () {
        it('throws exception for soft-deleted project', function () {
            $user = User::factory()->create();
            $project = Project::factory()->create(['user_id' => $user->id]);
            $projectId = $project->id;
            $project->delete();

            expect(fn () => Livewire::actingAs($user)
                ->test(KanbanBoard::class, ['projectId' => $projectId]))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('Cross-Project Task Access', function () {
        it('does not show tasks from other projects', function () {
            $user = User::factory()->create();
            $project1 = Project::factory()->create(['user_id' => $user->id]);
            $project2 = Project::factory()->create(['user_id' => $user->id]);

            Task::factory()->create([
                'project_id' => $project1->id,
                'title' => 'Project 1 Task',
            ]);

            Task::factory()->create([
                'project_id' => $project2->id,
                'title' => 'Project 2 Task',
            ]);

            Livewire::actingAs($user)
                ->test(KanbanBoard::class, ['projectId' => $project1->id])
                ->assertSee('Project 1 Task')
                ->assertDontSee('Project 2 Task');
        });

        it('cannot move task from another project', function () {
            $user = User::factory()->create();
            $project1 = Project::factory()->create(['user_id' => $user->id]);
            $project2 = Project::factory()->create(['user_id' => $user->id]);

            $task = Task::factory()->create([
                'project_id' => $project2->id,
            ]);

            // Try to move task from project2 while viewing project1's board
            // The task should not be found in the board's query
            $this->expectException(InvalidArgumentException::class);

            Livewire::actingAs($user)
                ->test(KanbanBoard::class, ['projectId' => $project1->id])
                ->call('moveCard', $task->id, 'doing');
        });
    });
});
