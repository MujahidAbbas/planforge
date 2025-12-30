<?php

namespace App\Livewire\Projects\Tabs;

use App\Enums\TaskStatus;
use App\Models\Task;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Kanban extends Component
{
    public string $projectId;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    #[Computed]
    public function todoTasks()
    {
        return Task::query()
            ->where('project_id', $this->projectId)
            ->where('status', TaskStatus::Todo)
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function doingTasks()
    {
        return Task::query()
            ->where('project_id', $this->projectId)
            ->where('status', TaskStatus::Doing)
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function doneTasks()
    {
        return Task::query()
            ->where('project_id', $this->projectId)
            ->where('status', TaskStatus::Done)
            ->orderBy('position')
            ->get();
    }

    public function moveTask(string $taskId, string $status): void
    {
        $task = Task::find($taskId);
        if ($task && $task->project_id === $this->projectId) {
            $task->update(['status' => TaskStatus::from($status)]);
            unset($this->todoTasks, $this->doingTasks, $this->doneTasks);
        }
    }

    #[On('tasksUpdated')]
    public function refreshTasks(): void
    {
        unset($this->todoTasks, $this->doingTasks, $this->doneTasks);
    }

    public function render()
    {
        return view('livewire.projects.tabs.kanban');
    }
}
