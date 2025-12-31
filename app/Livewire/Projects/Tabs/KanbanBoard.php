<?php

namespace App\Livewire\Projects\Tabs;

use App\Actions\GenerateTasksFromTechSpec;
use App\Enums\PlanRunStepStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSet;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

class KanbanBoard extends Component implements HasActions, HasBoard, HasForms
{
    use InteractsWithActions {
        InteractsWithBoard::getDefaultActionRecord insteadof InteractsWithActions;
    }
    use InteractsWithBoard;
    use InteractsWithForms;

    public string $projectId;

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(
                Task::query()->where('project_id', $this->projectId)
            )
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('doing')->label('In Progress')->color('info'),
                Column::make('done')->label('Done')->color('success'),
            ])
            ->cardSchema(fn (Schema $schema) => $schema
                ->extraAttributes(['class' => 'space-y-2'])
                ->components([
                    TextEntry::make('description')
                        ->hiddenLabel()
                        ->limit(100)
                        ->color('gray')
                        ->size('sm'),
                    TextEntry::make('badges')
                        ->hiddenLabel()
                        ->state(fn ($record) => collect([
                            $record->category ? [
                                'label' => match ($record->category?->value ?? $record->category) {
                                    'backend' => 'Backend',
                                    'frontend' => 'Frontend',
                                    'db' => 'DB',
                                    'infra' => 'Infra',
                                    'tests' => 'Tests',
                                    'docs' => 'Docs',
                                    default => $record->category,
                                },
                                'color' => match ($record->category?->value ?? $record->category) {
                                    'backend' => 'success',
                                    'frontend' => 'info',
                                    'db' => 'warning',
                                    'tests' => 'primary',
                                    default => 'gray',
                                },
                            ] : null,
                            $record->estimate ? [
                                'label' => $record->estimate,
                                'color' => 'gray',
                                'icon' => true,
                            ] : null,
                        ])->filter()->values()->toArray())
                        ->view('components.task-badges'),
                ]))
            ->columnActions([
                CreateAction::make()
                    ->label('Add task')
                    ->icon('heroicon-o-plus')
                    ->model(Task::class)
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        TextInput::make('estimate')
                            ->label('Estimate')
                            ->placeholder('e.g., 2h, 1d, 3pts'),
                    ])
                    ->mutateFormDataUsing(function (array $data, array $arguments): array {
                        if (isset($arguments['column'])) {
                            $data['project_id'] = $this->projectId;
                            $data['status'] = $arguments['column'];
                            $data['position'] = $this->getBoardPositionInColumn($arguments['column']);
                        }

                        return $data;
                    }),
            ])
            ->cardActions([
                EditAction::make()
                    ->model(Task::class)
                    ->form([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                        TextInput::make('estimate')
                            ->label('Estimate')
                            ->placeholder('e.g., 2h, 1d, 3pts'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'todo' => 'To Do',
                                'doing' => 'In Progress',
                                'done' => 'Done',
                            ])
                            ->required(),
                    ]),
                DeleteAction::make()
                    ->model(Task::class)
                    ->requiresConfirmation(),
            ])
            ->cardAction('edit');
    }

    #[On('tasksUpdated')]
    #[On('planRunCompleted')]
    #[On('taskGenerationStarted')]
    public function refreshBoard(): void
    {
        unset($this->latestTaskSet);
        unset($this->isStale);
        unset($this->isGeneratingTasks);
    }

    #[Computed]
    public function latestTaskSet(): ?TaskSet
    {
        return TaskSet::where('project_id', $this->projectId)
            ->with(['planRunStep', 'sourceTechVersion'])
            ->latest()
            ->first();
    }

    #[Computed]
    public function isStale(): bool
    {
        return $this->latestTaskSet?->isStale() ?? false;
    }

    #[Computed]
    public function isGeneratingTasks(): bool
    {
        $taskSet = $this->latestTaskSet;

        if (! $taskSet) {
            return false;
        }

        return in_array($taskSet->status, [
            PlanRunStepStatus::Queued,
            PlanRunStepStatus::Running,
            PlanRunStepStatus::Delayed,
        ]);
    }

    public function regenerateTasks(): void
    {
        if ($this->isGeneratingTasks) {
            return;
        }

        $project = Project::findOrFail($this->projectId);
        $action = new GenerateTasksFromTechSpec;
        $action->handle($project);

        unset($this->latestTaskSet);
        unset($this->isStale);
        unset($this->isGeneratingTasks);
    }

    public function render()
    {
        return view('livewire.projects.tabs.kanban-board');
    }
}
