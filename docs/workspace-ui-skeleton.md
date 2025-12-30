Nice — Step 6 is where you build the “app shell” that everything plugs into. Here’s a **scalable Livewire 3 workspace UI skeleton** (tabs, loading patterns, regen hooks, and “plan run in progress” UX), with best-practice structure.

---

## 6.1 Create the routes (2 pages)

Keep it simple:

* `/projects` → list projects
* `/projects/{project}` → workspace

Laravel routes can point directly to Livewire page components. ([Laravel][1])

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/projects', \App\Livewire\Projects\Index::class)->name('projects.index');
    Route::get('/projects/{project}', \App\Livewire\Projects\Workspace::class)->name('projects.workspace');
});
```

---

## 6.2 Add an app layout “shell” + fast navigation

Use Livewire’s SPA-like navigation for snappy UX by putting `wire:navigate` on your links. ([Laravel][2])

Example (sidebar links):

```blade
<a href="{{ route('projects.index') }}" wire:navigate>Projects</a>
<a href="{{ route('projects.workspace', $project) }}" wire:navigate>{{ $project->name }}</a>
```

This will matter once your workspace page becomes heavier.

---

## 6.3 Build the Workspace page component (the hub)

### Key best practice: don’t store Eloquent models in public properties

Livewire properties are dehydrated/hydrated between requests, so keep public state serializable (IDs, strings), and load models via computed properties. ([Laravel][3])

### Workspace responsibilities

* Authenticate/authorize access
* Manage **active tab** (in URL)
* Render tab panels (PRD/Tech/Kanban/Export)
* Show plan-run status banner

Use `#[Url]` to store the tab in query string so it’s shareable/bookmarkable. ([Laravel][4])

```php
namespace App\Livewire\Projects;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Project;

class Workspace extends Component
{
    use AuthorizesRequests;

    public string $projectId;

    #[Url(as: 'tab', except: 'prd')]
    public string $tab = 'prd';

    public function mount(Project $project)
    {
        $this->authorize('view', $project); // policy-based auth :contentReference[oaicite:4]{index=4}
        $this->projectId = $project->id;
    }

    #[Computed]
    public function project(): Project
    {
        return Project::query()->whereKey($this->projectId)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.projects.workspace');
    }
}
```

---

## 6.4 Workspace Blade: tabs + panel slots

Use buttons/links to switch `$tab`. Because it’s `#[Url]`, the URL updates automatically. ([Laravel][4])

```blade
<div>
  <header class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">{{ $this->project->name }}</h1>

    {{-- Plan run status banner goes here --}}
    <livewire:projects.plan-run-banner :project-id="$projectId" />
  </header>

  <nav class="flex gap-2 mt-4">
    <button wire:click="$set('tab','prd')">PRD</button>
    <button wire:click="$set('tab','tech')">Tech</button>
    <button wire:click="$set('tab','kanban')">Kanban</button>
    <button wire:click="$set('tab','export')">Export</button>
  </nav>

  <section class="mt-6">
    @switch($tab)
      @case('prd')
        <livewire:projects.tabs.prd :project-id="$projectId" />
        @break

      @case('tech')
        <livewire:projects.tabs.tech :project-id="$projectId" />
        @break

      @case('kanban')
        <livewire:projects.tabs.kanban :project-id="$projectId" />
        @break

      @case('export')
        <livewire:projects.tabs.export :project-id="$projectId" />
        @break
    @endswitch
  </section>
</div>
```

**Why separate child components?**
It keeps hydration small and avoids re-rendering the kanban when you edit PRD.

---

## 6.5 Plan-run status + progress (polling = easiest)

While generation is running, you want the UI to update without manual refresh. Livewire polling is made for that: add `wire:poll` to refresh regularly. ([Laravel][5])

Create a small banner component that:

* shows “Running / Failed / Succeeded”
* shows which step is active
* shows “Regenerate” buttons later

```blade
<div wire:poll.2s>
  {{-- query latest plan run + steps --}}
  {{-- show progress UI --}}
</div>
```

Polling is the simplest “stepper UI” until you add broadcasting.

---

## 6.6 Use Events to keep tabs in sync

When a PRD regen finishes, you’ll want other panels to refresh (tech depends on prd). Livewire has a built-in event system to communicate between components. ([Laravel][6])

Pattern:

* PRD tab dispatches `docUpdated`
* Tech tab listens and refreshes computed state

This gives you clean coupling without calling across components directly.

---

## 6.7 PRD/Tech tab skeleton (just editing + versions later)

Start with a simple `textarea` + Save button. Keep it boring.

* `wire:model.defer="content"` (or `wire:model.live` later)
* Save creates a new `DocumentVersion` (append-only)

Livewire “Actions” are just methods you call from the UI. ([Laravel][7])

---

## 6.8 Kanban tab skeleton (MVP UI only)

For now:

* 3 columns (todo/doing/done)
* render tasks grouped by status
* clicking a card opens a detail panel later

Don’t implement drag-drop yet if you’re still shaping DB queries. Just render first.

---

## 6.9 Authorization + policies (do it now, not later)

In every “workspace” entry point, enforce policies. Laravel recommends using policies to group model authorization. ([Laravel][8])

* `ProjectPolicy@view`
* `TaskPolicy@update` etc.

---

# What you should build first (in order)

1. **Workspace page** that loads a project and shows tabs
2. **PRD tab** with “empty state” + placeholder editor
3. **Tech tab** same as PRD
4. **Plan run banner** with `wire:poll` status updates ([Laravel][5])
5. **Kanban tab** render-only list (no drag/drop yet)

---

If you paste your current route file + your intended folder structure for Livewire components, I’ll map it into an exact **component tree** (namespaces + files) and the minimal blade markup for a clean layout (sidebar + workspace header + tabs).

[1]: https://laravel.com/docs/12.x/routing?utm_source=chatgpt.com "Routing - Laravel 12.x - The PHP Framework For Web ..."
[2]: https://livewire.laravel.com/docs/3.x/navigate?utm_source=chatgpt.com "Navigate"
[3]: https://livewire.laravel.com/docs/3.x/properties?utm_source=chatgpt.com "Properties"
[4]: https://livewire.laravel.com/docs/3.x/url?utm_source=chatgpt.com "URL Query Parameters"
[5]: https://livewire.laravel.com/docs/3.x/wire-poll?utm_source=chatgpt.com "wire:poll"
[6]: https://livewire.laravel.com/docs/3.x/events?utm_source=chatgpt.com "Events"
[7]: https://livewire.laravel.com/docs/3.x/actions?utm_source=chatgpt.com "Actions | Laravel Livewire"
[8]: https://laravel.com/docs/12.x/authorization?utm_source=chatgpt.com "Authorization - Laravel 12.x - The PHP Framework For Web ..."
