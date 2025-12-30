Awesome — Step 12 is where your app becomes **shareable + verifiable**.

Below is a **practical export design** (that’s genuinely useful for GitHub portfolios) and a **PestPHP (latest) test plan** with ready-to-copy examples.

---

## Step 12A: Add exports

### A1) What to export (high-value for your app)

I’d ship **two exports** first:

1. **“Project Kit” ZIP (recommended)**

* `README.md` (project overview + links)
* `docs/PRD.md` (active PRD version)
* `docs/TECH.md` (active Tech Spec version)
* `kanban/tasks.json` (structured tasks for re-import)
* `kanban/tasks.csv` (easy for humans + GitHub/PM tools)
* `meta.json` (provider/model, generated_at, version ids)

2. **Tasks JSON (API-style)**

* A clean JSON response so people can integrate or script it.

ZIP download is straightforward using Laravel’s `Storage::download` (or `response()->download`). ([Laravel][1])

---

### A2) Data model for exports (minimal but scalable)

Create an `exports` table so downloads are traceable:

* `id` (uuid)
* `project_id`
* `user_id`
* `type` (`project_kit`)
* `status` (`building|ready|failed`)
* `disk` (`local`/`s3`)
* `path` (e.g. `exports/{project}/{export}.zip`)
* `filename` (ASCII-safe)
* `size_bytes`
* `expires_at`
* `error_message`
* timestamps

> Store files on a **private disk** and download via controller to enforce auth.

---

### A3) Implementation structure

**Files**

* `app/Services/Exports/ProjectKitExporter.php`
* `app/Http/Controllers/ProjectExportController.php`
* routes:

  * `GET /projects/{project}/exports/project-kit` (downloads latest or generates then downloads)

**Exporter service (core idea)**

* Write markdown/json/csv into a temp folder
* Zip it (`ZipArchive`)
* Save to `Storage::disk($disk)->put($zipPath, file_get_contents($tmpZip))`

**Controller**

* `authorize('view', $project)`
* create export record
* build zip (sync now, job later)
* return `Storage::download($path, $filename)` ([Laravel][1])

---

### A4) Recommended endpoints

```php
Route::middleware('auth')->group(function () {
    Route::get('/projects/{project}/exports/project-kit', [ProjectExportController::class, 'projectKit'])
        ->name('projects.exports.projectKit');

    Route::get('/projects/{project}/exports/tasks.json', [ProjectExportController::class, 'tasksJson'])
        ->name('projects.exports.tasksJson');
});
```

---

## Step 12B: Add tests (PestPHP latest)

### B1) Install / confirm Pest (latest)

Pest’s docs show installation via Composer and note the current requirement baseline (PHP 8.3+). ([Pest][2])
Laravel supports running tests via `./vendor/bin/pest` or `php artisan test`. ([Laravel][3])

---

### B2) Test plan (what actually matters)

Write these **Feature tests** (most valuable in Laravel): ([Laravel][3])

#### 1) Export ZIP downloads + contains expected files

* Authenticate
* Hit export route
* Assert it’s a download (`assertDownload`)
* Assert zip exists in storage
* Open zip and verify entries

Laravel provides `assertDownload()` on `TestResponse`. ([Laravel API][4])

#### 2) Tasks JSON export returns correct shape

* Hit tasks JSON endpoint
* `assertOk()`
* `assertJsonStructure()` for stable contract

#### 3) Unauthorized user cannot export

* Another user → `assertForbidden()` / 403

#### 4) Pipeline export content is stable (no live LLM calls)

When you test generation or regeneration flows, **fake Prism responses** using `Prism::fake()` / `StructuredResponseFake`. ([Prism][5])

---

### B3) Example Pest tests

#### Feature: ZIP export

```php
<?php

use Illuminate\Support\Facades\Storage;
use ZipArchive;

use function Pest\Laravel\{actingAs, get};

it('downloads a project kit zip export', function () {
    Storage::fake('local');

    $user = \App\Models\User::factory()->create();
    $project = \App\Models\Project::factory()->for($user, 'owner')->create();

    // Seed “active” artifacts (adapt to your schema)
    \App\Models\PrdVersion::factory()->for($project)->create(['content' => '# PRD']);
    \App\Models\TechVersion::factory()->for($project)->create(['content' => '# Tech']);
    \App\Models\Task::factory()->for($project)->create(['title' => 'Do X', 'status' => 'todo']);

    actingAs($user);

    $response = get(route('projects.exports.projectKit', $project));

    $response->assertOk();
    $response->assertDownload(); // can also pass expected filename
    // assertDownload is available on Laravel TestResponse
    // :contentReference[oaicite:7]{index=7}

    // If you make the exporter write to a deterministic path like:
    // exports/{project_id}/latest.zip
    $zipRelPath = "exports/{$project->id}/latest.zip";
    Storage::disk('local')->assertExists($zipRelPath);

    $zipAbsPath = Storage::disk('local')->path($zipRelPath);

    $zip = new ZipArchive();
    expect($zip->open($zipAbsPath))->toBeTrue();

    expect($zip->locateName('README.md'))->not->toBeFalse();
    expect($zip->locateName('docs/PRD.md'))->not->toBeFalse();
    expect($zip->locateName('docs/TECH.md'))->not->toBeFalse();
    expect($zip->locateName('kanban/tasks.json'))->not->toBeFalse();

    $zip->close();
});
```

#### Feature: Tasks JSON export

```php
<?php

use function Pest\Laravel\{actingAs, get};

it('exports tasks as json with stable structure', function () {
    $user = \App\Models\User::factory()->create();
    $project = \App\Models\Project::factory()->for($user, 'owner')->create();

    \App\Models\Task::factory()->for($project)->count(2)->create(['status' => 'todo']);

    actingAs($user);

    $response = get(route('projects.exports.tasksJson', $project));

    $response->assertOk();
    $response->assertJsonStructure([
        'project' => ['id', 'name'],
        'tasks' => [
            '*' => ['id', 'title', 'status', 'position', 'created_at', 'updated_at'],
        ],
    ]);
});
```

#### Feature: Authorization

```php
<?php

use function Pest\Laravel\{actingAs, get};

it('forbids exporting a project you do not own', function () {
    $owner = \App\Models\User::factory()->create();
    $intruder = \App\Models\User::factory()->create();

    $project = \App\Models\Project::factory()->for($owner, 'owner')->create();

    actingAs($intruder);

    get(route('projects.exports.projectKit', $project))
        ->assertForbidden();
});
```

---

### B4) Testing LLM pipelines without calling real providers

Use Prism’s built-in fakes to make your generation tests deterministic. ([Prism][5])

Example pattern:

* `Prism::fake([StructuredResponseFake::make()->withStructured([...])])`
* Run your job/service
* Assert DB rows created and status transitions correct

(Prism explicitly documents `Prism::fake()` and `StructuredResponseFake` for structured output tests.) ([Prism][5])

---

## What I’d do first in Step 12 (order)

1. Implement **Tasks JSON export** (fast + tests easy)
2. Implement **Project Kit ZIP exporter** (service class + download route) using `Storage::download` ([Laravel][1])
3. Add the **ZIP feature test** with `assertDownload()` ([Laravel API][4])
4. Add Prism-fake tests for generation/regeneration paths ([Prism][5])

---

If you paste your current “active pointers” (how you pick active PRD/Tech/TaskSet), I’ll tailor the exporter file list + exact queries so your ZIP always exports the right versions.

[1]: https://laravel.com/docs/12.x/filesystem "File Storage - Laravel 12.x - The PHP Framework For Web Artisans"
[2]: https://pestphp.com/docs "Installation | Pest - The elegant PHP Testing Framework"
[3]: https://laravel.com/docs/12.x/testing "Testing: Getting Started - Laravel 12.x - The PHP Framework For Web Artisans"
[4]: https://api.laravel.com/docs/12.x/Illuminate/Testing/TestResponse.html "Illuminate\Testing\TestResponse | Laravel API"
[5]: https://prismphp.com/core-concepts/testing.html "Testing | Prism"
