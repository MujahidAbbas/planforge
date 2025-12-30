<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\PlanRunStatus;
use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\PlanRun;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@planforge.dev'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        // Create demo project
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Task Management App',
            'idea' => 'A simple task management application where users can create projects, add tasks, set priorities, and track progress. Should have a clean, modern UI with drag-and-drop functionality.',
            'constraints' => [
                'Must be mobile-responsive',
                'Use SQLite for simplicity',
                'No external auth providers required',
            ],
            'preferred_provider' => 'anthropic',
            'preferred_model' => 'claude-sonnet-4-20250514',
        ]);

        // Create a completed plan run
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
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(3),
        ]);

        // Create PRD document
        $prdDoc = Document::create([
            'project_id' => $project->id,
            'type' => DocumentType::Prd,
        ]);

        $prdVersion = DocumentVersion::create([
            'document_id' => $prdDoc->id,
            'plan_run_id' => $planRun->id,
            'content_md' => $this->getDemoPrd(),
        ]);

        $prdDoc->update(['current_version_id' => $prdVersion->id]);

        // Create Tech Spec document
        $techDoc = Document::create([
            'project_id' => $project->id,
            'type' => DocumentType::Tech,
        ]);

        $techVersion = DocumentVersion::create([
            'document_id' => $techDoc->id,
            'plan_run_id' => $planRun->id,
            'content_md' => $this->getDemoTechSpec(),
        ]);

        $techDoc->update(['current_version_id' => $techVersion->id]);

        // Create sample tasks
        $tasks = [
            ['title' => 'Set up Laravel project structure', 'status' => TaskStatus::Done, 'estimate' => 'S'],
            ['title' => 'Create Task model and migration', 'status' => TaskStatus::Done, 'estimate' => 'S'],
            ['title' => 'Build task list component', 'status' => TaskStatus::Done, 'estimate' => 'M'],
            ['title' => 'Implement drag-and-drop sorting', 'status' => TaskStatus::Doing, 'estimate' => 'M'],
            ['title' => 'Add task priority levels', 'status' => TaskStatus::Doing, 'estimate' => 'S'],
            ['title' => 'Create project dashboard', 'status' => TaskStatus::Todo, 'estimate' => 'L'],
            ['title' => 'Add task filtering and search', 'status' => TaskStatus::Todo, 'estimate' => 'M'],
            ['title' => 'Implement task due dates', 'status' => TaskStatus::Todo, 'estimate' => 'M'],
            ['title' => 'Add mobile responsive styles', 'status' => TaskStatus::Todo, 'estimate' => 'M'],
            ['title' => 'Write feature tests', 'status' => TaskStatus::Todo, 'estimate' => 'L'],
        ];

        foreach ($tasks as $index => $taskData) {
            Task::create([
                'project_id' => $project->id,
                'plan_run_id' => $planRun->id,
                'title' => $taskData['title'],
                'description' => 'Implementation task for the task management app.',
                'status' => $taskData['status'],
                'position' => $index,
                'estimate' => $taskData['estimate'],
                'labels' => ['demo'],
                'acceptance_criteria' => ['Task is completed and tested'],
            ]);
        }

        $this->command->info('Demo project created: '.$project->name);
        $this->command->info('Visit /projects/'.$project->id.' to view it.');
    }

    private function getDemoPrd(): string
    {
        return <<<'MD'
# Task Management App - Product Requirements Document

## 1. Overview

A lightweight task management application designed for individual developers and small teams to organize their work efficiently.

## 2. Goals

- Provide a simple, intuitive interface for task management
- Enable quick task creation and organization
- Support basic project grouping
- Deliver a responsive experience across devices

## 3. User Stories

### Task Management
- As a user, I can create tasks with a title and description
- As a user, I can set task priority (low, medium, high)
- As a user, I can mark tasks as complete
- As a user, I can drag and drop tasks to reorder them

### Project Organization
- As a user, I can create projects to group related tasks
- As a user, I can view all tasks within a project
- As a user, I can archive completed projects

## 4. Features

### Core Features (MVP)
1. Task CRUD operations
2. Task status management (Todo, In Progress, Done)
3. Drag-and-drop task reordering
4. Basic project grouping

### Future Features
- Due dates and reminders
- Task labels/tags
- Search and filtering
- Team collaboration

## 5. Non-Functional Requirements

- Page load time under 2 seconds
- Mobile-responsive design
- SQLite database for simplicity
- No external authentication required for MVP
MD;
    }

    private function getDemoTechSpec(): string
    {
        return <<<'MD'
# Task Management App - Technical Specification

## 1. Technology Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Build Tools**: Vite

## 2. Database Schema

### Projects Table
```sql
- id (ULID, primary key)
- name (string)
- description (text, nullable)
- status (enum: active, archived)
- created_at, updated_at
- deleted_at (soft delete)
```

### Tasks Table
```sql
- id (ULID, primary key)
- project_id (foreign key)
- title (string)
- description (text, nullable)
- status (enum: todo, doing, done)
- priority (enum: low, medium, high)
- position (integer)
- created_at, updated_at
- deleted_at (soft delete)
```

## 3. API Endpoints

### Projects
- `GET /projects` - List all projects
- `POST /projects` - Create project
- `GET /projects/{id}` - View project
- `PUT /projects/{id}` - Update project
- `DELETE /projects/{id}` - Delete project

### Tasks
- `GET /projects/{id}/tasks` - List tasks
- `POST /projects/{id}/tasks` - Create task
- `PUT /tasks/{id}` - Update task
- `DELETE /tasks/{id}` - Delete task
- `PATCH /tasks/{id}/position` - Reorder task

## 4. Component Architecture

```
Livewire Components:
├── ProjectList - Displays all projects
├── ProjectView - Single project with tasks
├── TaskBoard - Kanban-style task board
├── TaskCard - Individual task display
└── TaskForm - Create/edit task modal
```

## 5. Security Considerations

- CSRF protection on all forms
- Input validation and sanitization
- Soft deletes to prevent data loss
- Rate limiting on API endpoints
MD;
    }
}
