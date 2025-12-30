Great choice for a “fast path.” **Flowforge** gets you a production-grade Kanban (drag/drop ordering + smart positioning + pagination) without you hand-rolling all the UX. ([Relaticle][1])

Below is a **step-by-step implementation** for your **Workspace → Kanban tab** (standalone Livewire integration).

---

## 9.1 Install Flowforge (and Filament, required)

Flowforge requires **Filament v4** (even for “standalone Livewire” boards), plus PHP/Laravel requirements. ([Relaticle][2])

```bash
composer require filament/filament:"^4.0"
php artisan filament:install --panels
composer require relaticle/flowforge
```

(Those Filament install commands are straight from Filament’s v4 docs.) ([Filament][3])

> Also make sure `ext-bcmath` is enabled, since Flowforge uses it for position calculations. ([GitHub][4])

---

## 9.2 Add the required Kanban fields to your `tasks` table

Flowforge needs:

* a **column identifier** (you already have `status`)
* a **position column** for drag/drop ordering (`position` by default)

Flowforge provides a migration helper `flowforgePositionColumn()` which also handles DB-specific collations automatically. ([Relaticle][5])

```php
Schema::table('tasks', function (Blueprint $table) {
    // default name is 'position' OR you can pass your own column name
    $table->flowforgePositionColumn('position');
});
```

If you already created something like `board_order` earlier, you have two good options:

* **Rename to `position`** (simplest), or
* Keep it and do: `flowforgePositionColumn('board_order')` + `->positionIdentifier('board_order')` in the board config. ([Relaticle][5])

---

## 9.3 Ensure your task statuses match your columns

Flowforge columns are just strings (e.g. `todo`, `doing`, `done`) and it uses your chosen `columnIdentifier('status')`. ([Relaticle][6])

So align your AI structured output from Step 8 to exactly these column keys.

---

## 9.4 Create the Kanban Livewire component (standalone pattern)

Flowforge’s docs show a “Standalone Livewire” integration that renders the board via `{{ $this->board }}`. ([Relaticle][6])

Create something like:

`app/Livewire/Projects/Tabs/KanbanBoard.php`

```php
namespace App\Livewire\Projects\Tabs;

use Livewire\Component;
use App\Models\Task;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class KanbanBoard extends Component implements HasBoard, HasActions, HasForms
{
    use InteractsWithBoard;
    use InteractsWithActions;
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
                Column::make('doing')->label('Doing')->color('blue'),
                Column::make('done')->label('Done')->color('green'),
            ]);
    }

    public function render()
    {
        return view('livewire.projects.tabs.kanban-board');
    }
}
```

Blade:

`resources/views/livewire/projects/tabs/kanban-board.blade.php`

```blade
<div>
    {{ $this->board }}
</div>
```

This is the exact integration style Flowforge documents for standalone Livewire components. ([Relaticle][6])

---

## 9.5 Add rich card UI + Create/Edit actions (still “fast”, feels premium)

Flowforge lets you:

* define **cardSchema** (Filament schema builder)
* add **column actions** (e.g., “Add Task”)
* add **card actions** (Edit/Delete)
* enable **search + filters**

All supported in their customization docs. ([Relaticle][7])

Example upgrades inside `board()`:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;

return $board
    ->query(Task::query()->where('project_id', $this->projectId))
    ->columnIdentifier('status')
    ->positionIdentifier('position')
    ->columns([
        Column::make('todo')->label('To Do')->color('gray'),
        Column::make('doing')->label('Doing')->color('blue'),
        Column::make('done')->label('Done')->color('green'),
    ])
    ->cardSchema(fn (Schema $schema) => $schema->components([
        TextEntry::make('title'),
        TextEntry::make('estimate')->badge(),
    ]))
    ->columnActions([
        CreateAction::make()
            ->label('Add task')
            ->model(Task::class)
            ->form([
                TextInput::make('title')->required(),
                TextInput::make('estimate'),
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
        EditAction::make()->model(Task::class),
        DeleteAction::make()->model(Task::class),
    ])
    ->cardAction('edit');
```

This pattern is lifted directly from Flowforge’s customization examples (card schema + column actions + card actions). ([Relaticle][7])

---

## 9.6 Make sure the styles actually compile

Flowforge’s install guide says you need a **custom Filament theme** and to add the plugin views as a Tailwind `@source` so the CSS build includes the classes. ([Relaticle][2])

1. Create theme:

```bash
php artisan make:filament-theme
```

(That command is in Filament v4 styling docs.) ([Filament][8])

2. Add Flowforge as a Tailwind `@source` in your theme CSS, as Flowforge documents:

```css
/* resources/css/filament/admin/theme.css */
@source "../../../../vendor/relaticle/flowforge/resources/views/**/*.blade.php";
```

([Relaticle][2])

---

## 9.7 Drop it into your Workspace “Kanban” tab

Where you currently render your kanban tab:

```blade
<livewire:projects.tabs.kanban-board :project-id="$projectId" />
```

Now your Step 8 structured tasks become “real cards” instantly.

---

## 9.8 Ops tip: repair positions if anything gets weird

Flowforge ships a repair command to fix corrupted/missing position data. ([Relaticle][5])

```bash
php artisan flowforge:repair-positions
```

---

### Quick sanity checklist

* [ ] `tasks.status` contains `todo/doing/done`
* [ ] `tasks.position` exists (via `flowforgePositionColumn`)
* [ ] Flowforge CSS included via theme `@source`
* [ ] Board query is scoped to `project_id`

---

If you tell me whether you’re on **Laravel 11 or 12** (and whether you already installed Filament), I’ll tailor the exact install/theme steps so you don’t hit version friction—**but you can already proceed with the component + migration right now**.

[1]: https://relaticle.github.io/flowforge/ "Filament/Livewire Kanban Boards - Flowforge"
[2]: https://relaticle.github.io/flowforge/getting-started/installation "Installation - Flowforge"
[3]: https://filamentphp.com/docs/4.x/introduction/installation/?utm_source=chatgpt.com "Installation - Introduction"
[4]: https://github.com/Relaticle/flowforge "GitHub - Relaticle/flowforge: Laravel Kanban boards with intelligent pagination, drag-and-drop ordering, and flexible    integration patterns for Filament and Livewire applications."
[5]: https://relaticle.github.io/flowforge/essentials/database-schema "Database Schema - Flowforge"
[6]: https://relaticle.github.io/flowforge/essentials/integration-patterns "Integration Patterns - Flowforge"
[7]: https://relaticle.github.io/flowforge/essentials/customization "Customization - Flowforge"
[8]: https://filamentphp.com/docs/4.x/styling/overview/?utm_source=chatgpt.com "Overview - Customizing styling"
