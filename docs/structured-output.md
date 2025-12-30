Awesome — this is the step that makes your kanban **deterministic** (no “parse LLM text” pain). Here’s a **scalable, best-practice** way to implement **structured task output** with PrismPHP, wired into your existing **PlanRun / PlanRunStep** pipeline.

---

## Step 8 goal

Generate tasks as **validated JSON** (via a schema) → persist to DB → render reliably in kanban.

Prism supports this directly with `Prism::structured()->withSchema(...)->asStructured()` and returns `$response->structured` as an array. ([Prism][1])

---

## 1) Create a Task schema (ObjectSchema root)

**Best practice:** Put your schema in a dedicated class, not inside the job.

Important: Prism docs note the **root schema should be an `ObjectSchema`** for OpenAI structured output. ([Prism][1])

Create: `app/Domain/Planning/Schemas/TasksSchema.php`

```php
<?php

namespace App\Domain\Planning\Schemas;

use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Schema\EnumSchema;

final class TasksSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'kanban_tasks',
            description: 'Tasks ready to insert into a kanban board',
            properties: [
                new ArraySchema(
                    name: 'tasks',
                    description: 'List of task cards',
                    items: new ObjectSchema(
                        name: 'task',
                        description: 'A single task card',
                        properties: [
                            new StringSchema('title', 'Short task title'),
                            new StringSchema('description', 'Implementation notes'),
                            new ArraySchema(
                                name: 'acceptance_criteria',
                                description: 'Acceptance criteria list',
                                items: new StringSchema('item', 'A single acceptance criteria item')
                            ),
                            new EnumSchema(
                                name: 'suggested_column',
                                description: 'Kanban column',
                                options: ['todo', 'doing', 'done']
                            ),
                            new StringSchema('estimate', 'Rough estimate like 2h / 1d / 3sp', nullable: true),
                            new ArraySchema(
                                name: 'labels',
                                description: 'Labels like backend, db, ui, tests',
                                items: new StringSchema('label', 'A label')
                            ),
                            new ArraySchema(
                                name: 'depends_on',
                                description: 'Task titles this depends on (best-effort)',
                                items: new StringSchema('dep', 'Dependency')
                            ),
                            new StringSchema('epic_title', 'Epic name for grouping', nullable: true),
                            new StringSchema('story_title', 'Story name for grouping', nullable: true),
                        ],
                        // NOTE: for OpenAI strict structured output, ALL fields must appear in requiredFields.
                        // optional fields should be nullable=true instead. :contentReference[oaicite:2]{index=2}
                        requiredFields: [
                            'title',
                            'description',
                            'acceptance_criteria',
                            'suggested_column',
                            'estimate',
                            'labels',
                            'depends_on',
                            'epic_title',
                            'story_title',
                        ],
                    )
                ),
            ],
            requiredFields: ['tasks']
        );
    }
}
```

### Why this schema works well

* `suggested_column` is an enum → prevents weird values
* You can keep optional fields (`estimate`, `epic_title`, `story_title`) but mark them `nullable`
* For OpenAI strict mode, you **must** list *all* fields in `requiredFields` (even nullable ones). ([Prism][2])

---

## 2) Create a Tasks prompt template

Create:

* `resources/views/prompts/tasks/system.blade.php`
* `resources/views/prompts/tasks/user.blade.php`

**System prompt (example)**:

```blade
You are a senior product engineer.
Return ONLY data that matches the provided schema.
Generate implementation-ready tasks with clear acceptance criteria.
Prefer small tasks (1–4 hours) over large vague tasks.
Use suggested_column: todo/doing/done.
```

**User prompt (example)**:

```blade
Project: {{ $project->name }}

Idea:
{{ $project->idea }}

Constraints:
{{ json_encode($project->constraints ?? [], JSON_PRETTY_PRINT) }}

PRD (context):
{{ $prd }}

Tech spec (context):
{{ $tech }}

Generate a kanban backlog for an MVP.
Keep dependencies minimal and realistic.
```

---

## 3) Implement `GenerateTasksJob` (structured output call)

This follows the same shape as PRD/Tech jobs, but uses `Prism::structured()`.

Prism quick-start shows `Prism::structured()->withSchema()->asStructured()` and accessing `$response->structured`. ([Prism][1])

```php
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use App\Domain\Planning\Schemas\TasksSchema;

$schema = TasksSchema::make();

$builder = Prism::structured()
    ->using(Provider::OpenAI, 'gpt-4o')
    ->withSchema($schema)
    ->withSystemPrompt(view('prompts.tasks.system')->render())
    ->withPrompt(view('prompts.tasks.user', [
        'project' => $project,
        'prd' => $prdText,
        'tech' => $techText,
    ])->render());

// If using OpenAI strict structured output, enable strict mode.
// Prism documents OpenAI strict structured schema options. :contentReference[oaicite:5]{index=5}
$builder = $builder->withProviderOptions([
    'schema' => ['strict' => true]
]);

$response = $builder->asStructured();

$tasks = $response->structured['tasks'];
```

### Provider handling tip

* Apply `strict` only for OpenAI.
* Other providers may behave differently; Prism notes provider requirements vary. ([Prism][1])

---

## 4) Persist tasks safely (scalable approach)

### Recommended MVP strategy: “replace AI-generated backlog”

When you regenerate tasks, the simplest/cleanest rule is:

* Delete (or soft-delete) previous **AI-generated** tasks for the project
* Insert the new set, linked to `plan_run_id` + `plan_run_step_id`

**Why:** avoids merge complexity and keeps “regen” predictable.

Implementation notes:

* Wrap persistence in a DB transaction
* Assign `board_order` deterministically per column (e.g., 1000, 2000… or 1, 2, 3…)
* Normalize `suggested_column` to your DB `status`

Example mapping:

```php
$status = match ($task['suggested_column']) {
  'todo' => 'todo',
  'doing' => 'doing',
  'done' => 'done',
  default => 'todo',
};
```

---

## 5) Optional but powerful: use Tools with structured output (for “merge-ish” behavior)

If you want the model to *look at existing tasks* and avoid duplicates, you can provide a tool like `list_existing_tasks(project_id)`.

Prism explicitly supports combining tools with structured output, but you **must** set `maxSteps >= 2` so the model can call tools and then output the schema. ([Prism][3])
Also note: only the **final** step contains structured output when tools are involved. ([Prism][3])

Use this later; don’t block MVP on it.

---

## 6) Update the Kanban UI contract

Once tasks are persisted, your kanban tab becomes dead simple:

Query:

* `where project_id = ?`
* `orderBy status, board_order`

And render 3 lists.

Because the schema enforces `suggested_column`, you won’t get “random task buckets”.

---

## 7) Add tests for structured output (so OSS contributors trust it)

Prism has a Testing guide and calls out that OpenAI strict structured output tests should use an `ObjectSchema` root. ([Prism][4])

Write tests that:

* Fake structured output and ensure you persist tasks correctly
* Validate required fields exist (especially if you support multiple providers)

---

## Implementation checklist (copy/paste)

1. ✅ `TasksSchema::make()` (ObjectSchema root, enum column, nullable optional fields) ([Prism][1])
2. ✅ Prompt templates: `prompts/tasks/system|user`
3. ✅ `GenerateTasksJob` uses `Prism::structured()->withSchema()->asStructured()` ([Prism][1])
4. ✅ Persist tasks in a transaction (replace old AI tasks)
5. ✅ Kanban reads tasks grouped by status
6. ✅ Tests using Prism fakes (later but strongly recommended) ([Prism][4])

---

If you paste your **current `tasks` table columns** + your **PlanRunStep naming** (e.g., `tasks` step already exists or not), I’ll tailor the exact `GenerateTasksJob` + persistence code to your schema (including ordering strategy and safe “replace tasks” behavior).

[1]: https://prismphp.com/core-concepts/structured-output.html "Structured Output | Prism"
[2]: https://prismphp.com/providers/openai.html "OpenAI | Prism"
[3]: https://prismphp.com/core-concepts/tools-function-calling.html "Tools & Function Calling | Prism"
[4]: https://prismphp.com/core-concepts/testing.html?utm_source=chatgpt.com "Testing - Prism"
