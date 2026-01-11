<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\PlanRunStatus;
use App\Enums\PlanRunStepStatus;
use App\Enums\ProjectStatus;
use App\Enums\StepType;
use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Epic;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Relaticle\Flowforge\Services\Rank;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed built-in templates first
        $this->call(TemplateSeeder::class);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'AI Task Manager',
            'idea' => 'Build an AI-powered task management app that can automatically break down project ideas into actionable tasks.',
            'constraints' => [
                'timeline' => '4 weeks',
                'budget' => 'low',
                'stack' => ['Laravel', 'Livewire', 'Tailwind'],
            ],
            'preferred_provider' => 'anthropic',
            'preferred_model' => 'claude-sonnet-4-20250514',
            'status' => ProjectStatus::Active,
        ]);

        $planRun = PlanRun::create([
            'project_id' => $project->id,
            'triggered_by' => $user->id,
            'status' => PlanRunStatus::Succeeded,
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-20250514',
            'input_snapshot' => [
                'idea' => $project->idea,
                'constraints' => $project->constraints,
            ],
            'metrics' => [
                'total_tokens' => 4500,
                'prompt_tokens' => 1200,
                'completion_tokens' => 3300,
                'latency_ms' => 12500,
            ],
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);

        $prdStep = PlanRunStep::create([
            'plan_run_id' => $planRun->id,
            'step' => StepType::Prd,
            'status' => PlanRunStepStatus::Succeeded,
            'attempt' => 1,
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-20250514',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(3),
        ]);

        $prdDoc = Document::create([
            'project_id' => $project->id,
            'type' => DocumentType::Prd,
        ]);

        $prdVersion = DocumentVersion::create([
            'document_id' => $prdDoc->id,
            'plan_run_id' => $planRun->id,
            'plan_run_step_id' => $prdStep->id,
            'created_by' => $user->id,
            'content_md' => "# AI Task Manager PRD\n\n## Overview\nAn AI-powered task management application...",
            'content_json' => [
                'sections' => ['Overview', 'Goals', 'Features', 'Success Metrics'],
            ],
            'summary' => 'PRD for AI Task Manager MVP',
        ]);

        $prdDoc->update(['current_version_id' => $prdVersion->id]);

        $epic = Epic::create([
            'project_id' => $project->id,
            'plan_run_id' => $planRun->id,
            'title' => 'Core Task Management',
            'summary' => 'Implement basic CRUD operations for tasks with Kanban board',
            'priority' => 1,
            'sort_order' => 0,
        ]);

        $story = Story::create([
            'epic_id' => $epic->id,
            'title' => 'As a user, I can create a new task',
            'description' => 'Users should be able to create tasks with title, description, and labels',
            'acceptance_criteria' => [
                'Task form with title, description, labels fields',
                'Validation for required fields',
                'Task appears in backlog after creation',
            ],
            'sort_order' => 0,
        ]);

        // Generate lexicographic positions for Flowforge
        $rank1 = Rank::forEmptySequence();
        $rank2 = Rank::after($rank1);
        $rank3 = Rank::after($rank2);

        Task::create([
            'project_id' => $project->id,
            'epic_id' => $epic->id,
            'story_id' => $story->id,
            'plan_run_id' => $planRun->id,
            'title' => 'Create Task model and migration',
            'description' => 'Set up the Task Eloquent model with proper relationships and migration',
            'acceptance_criteria' => ['Model created', 'Migration runs successfully', 'Relationships defined'],
            'estimate' => '2h',
            'labels' => ['backend', 'database'],
            'status' => TaskStatus::Done,
            'position' => $rank1->get(),
        ]);

        Task::create([
            'project_id' => $project->id,
            'epic_id' => $epic->id,
            'story_id' => $story->id,
            'plan_run_id' => $planRun->id,
            'title' => 'Build Livewire CreateTask component',
            'description' => 'Create the Livewire component for the task creation form',
            'acceptance_criteria' => ['Form renders correctly', 'Validation works', 'Task saves to DB'],
            'estimate' => '3h',
            'labels' => ['frontend', 'livewire'],
            'status' => TaskStatus::Doing,
            'position' => $rank2->get(),
        ]);

        Task::create([
            'project_id' => $project->id,
            'epic_id' => $epic->id,
            'title' => 'Implement Kanban drag-and-drop',
            'description' => 'Add drag-and-drop functionality for moving tasks between columns',
            'estimate' => '4h',
            'labels' => ['frontend', 'ux'],
            'status' => TaskStatus::Todo,
            'position' => $rank3->get(),
        ]);
    }
}
