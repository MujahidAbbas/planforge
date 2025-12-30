<?php

namespace App\Livewire\Projects\Tabs;

use App\Models\Task;
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
            ->cardSchema(fn (Schema $schema) => $schema->components([
                TextEntry::make('title')
                    ->weight('bold')
                    ->size('sm'),
                TextEntry::make('description')
                    ->limit(60)
                    ->color('gray'),
                TextEntry::make('estimate')
                    ->badge()
                    ->color('primary')
                    ->visible(fn ($record) => filled($record->estimate)),
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
    public function refreshBoard(): void
    {
        // Flowforge handles refresh automatically via Livewire reactivity
    }

    public function render()
    {
        return view('livewire.projects.tabs.kanban-board');
    }
}
