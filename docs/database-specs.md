Below is a **scalable, best-practice data model spec** for your “Idea → PRD/Tech docs → Breakdown → Kanban tasks” app (Laravel + Livewire + Prism). It’s designed to support: **versioning**, **re-generation**, **queue pipelines**, **provider/model switching**, and later **team/collaboration**.

I’ll give you:

1. **Key design decisions (best practices)**
2. **Tables + columns + indexes (MVP + scalable extensions)**
3. **Relationships (Eloquent)**
4. **Migration order + implementation notes**

---

## 1) Key design decisions

### IDs: use ULIDs (recommended) or ordered UUIDs

Laravel supports both `HasUlids` and `HasUuids` and notes they’re sortable for efficient indexing. ULIDs are shorter (26 chars) and URL-friendly. ([Laravel][1])
**Recommendation:** ULID primary keys for all domain tables.

### Keep “generated content” immutable via versions

Docs should be **append-only versions** so regen is just “create a new version and point current_version_id at it”.

### Store AI pipeline state as first-class records

Make plan runs queryable/debuggable: status, errors, provider/model, metrics. This is also how you’ll scale + operate.

### Avoid composite primary keys

Eloquent doesn’t support composite PKs; prefer single-column PK plus unique indexes where needed. ([Laravel][1])

### Rate limits / retries: design for it early

Prism throws `PrismRateLimitedException` and can provide `ProviderRateLimit` objects (limit/remaining/resetsAt) that you can store at the step level. ([Prism][2])
Laravel has a built-in rate limiting abstraction you can use for your own “app-level throttles”. ([Laravel][3])
(OpenAI also recommends retry/backoff patterns for 429s.) ([OpenAI Cookbook][4])

---

## 2) Data model spec

### A) `projects`

**Purpose:** User-owned container for idea + configuration.

**Columns**

* `id` (ulid pk)
* `user_id` (fk → users.id, index)
* `name` (string)
* `idea` (text) — raw idea
* `constraints` (json, nullable) — timeline, budget, stack prefs, etc.
* `preferred_provider` (string, nullable)
* `preferred_model` (string, nullable)
* `status` (string: `active|archived`)
* timestamps, soft deletes (optional)

**Indexes**

* `index(user_id, status)`
* `index(created_at)`

---

### B) `plan_runs`

**Purpose:** Each “Generate” click creates a run.

**Columns**

* `id` (ulid pk)
* `project_id` (fk, index)
* `triggered_by` (fk → users.id, nullable) — system runs, cron, etc.
* `status` (string: `queued|running|partial|failed|succeeded`)
* `provider` (string)
* `model` (string)
* `input_snapshot` (json) — freeze the project idea/constraints used
* `metrics` (json, nullable) — tokens, latency, cost estimate
* `error_message` (text, nullable)
* `started_at` / `finished_at` (timestamp nullable)
* timestamps

**Indexes**

* `index(project_id, created_at)`
* `index(status, created_at)`

---

### C) `plan_run_steps` (strongly recommended)

**Purpose:** Track each pipeline step separately (PRD/Tech/Breakdown/Tasks). This makes retries and partial success clean.

**Columns**

* `id` (ulid pk)
* `plan_run_id` (fk, index)
* `step` (string enum-ish: `prd|tech|breakdown|tasks`)
* `status` (string: `queued|running|failed|succeeded|skipped`)
* `attempt` (unsigned smallint)
* `provider` / `model` (string) — allow per-step overrides
* `prompt_hash` (string, nullable) — helps detect changes
* `request_meta` (json, nullable) — provider request id, etc.
* `rate_limits` (json, nullable) — store `ProviderRateLimit` array if available ([Prism][2])
* `error_message` (text, nullable)
* `started_at` / `finished_at` (timestamp nullable)
* timestamps

**Indexes**

* `unique(plan_run_id, step)` (1 row per step per run)
* `index(step, status)`

---

### D) `documents`

**Purpose:** Stable doc identity per project + type (PRD, Tech). Points to current version.

**Columns**

* `id` (ulid pk)
* `project_id` (fk, index)
* `type` (string: `prd|tech`)  *(you can add `ux`, `api`, etc. later)*
* `current_version_id` (ulid, nullable, fk → document_versions.id)
* timestamps

**Indexes**

* `unique(project_id, type)`

---

### E) `document_versions`

**Purpose:** Immutable versions of docs.

**Columns**

* `id` (ulid pk)
* `document_id` (fk, index)
* `plan_run_id` (fk, nullable, index)
* `plan_run_step_id` (fk, nullable, index)
* `created_by` (fk → users.id, nullable) — manual edits create versions too
* `content_md` (longtext)
* `content_json` (json, nullable) — optional outline, headings, etc.
* `summary` (text, nullable) — optional: store short summary for quick context
* timestamps

**Indexes**

* `index(document_id, created_at)`
* `index(plan_run_id)`

---

### F) `epics` and `stories` (optional in MVP, but recommended if you want breakdown)

If you’re going to generate a structured breakdown, store it.

#### `epics`

* `id` (ulid pk)
* `project_id` (fk, index)
* `plan_run_id` (fk, index)
* `title` (string)
* `summary` (text)
* `priority` (tinyint)
* `sort_order` (int) *(or decimal for easy reordering)*
* timestamps

Index: `index(project_id, sort_order)`

#### `stories`

* `id` (ulid pk)
* `epic_id` (fk, index)
* `title` (string)
* `description` (text)
* `acceptance_criteria` (json)
* `sort_order` (int)
* timestamps

Index: `index(epic_id, sort_order)`

---

### G) `tasks` (Kanban)

**Purpose:** The actionable backlog.

**Columns**

* `id` (ulid pk)
* `project_id` (fk, index)
* `epic_id` (fk, nullable, index)
* `story_id` (fk, nullable, index)
* `plan_run_id` (fk, nullable, index)
* `plan_run_step_id` (fk, nullable, index)
* `title` (string)
* `description` (text)
* `acceptance_criteria` (json, nullable)
* `estimate` (string, nullable) — “2h”, “3sp”
* `labels` (json, nullable) — store as json array for MVP
* `depends_on` (json, nullable) — array of task IDs or “keys”
* `status` (string: `todo|doing|done`)
* `board_order` (decimal(10,4) or int) — supports stable drag-drop ordering
* timestamps
* soft deletes (optional)

**Indexes**

* `index(project_id, status, board_order)`
* `index(project_id, created_at)`
* `index(plan_run_id)`

> Later, when you need filtering at scale, convert `labels` to a normalized `tags` + pivot.

---

## 3) Eloquent relationships (models)

* `User hasMany Projects`
* `Project hasMany PlanRuns`
* `PlanRun hasMany PlanRunSteps`
* `Project hasMany Documents`
* `Document hasMany DocumentVersions`
* `Project hasMany Tasks`
* `Epic hasMany Stories`, `Epic hasMany Tasks`
* `Story hasMany Tasks`

**Model traits**

* Use `HasUlids` on all domain models (Projects, PlanRuns, Documents, Tasks, etc.) ([Laravel][1])
* Use `$casts` for JSON columns (`constraints`, `input_snapshot`, `metrics`, `acceptance_criteria`, `labels`, etc.)
* Prefer constants/enums for statuses (even if stored as strings)

---

## 4) Migration order (do this next)

Laravel migrations are your “schema version control” and support indexes + foreign keys cleanly. ([Laravel][5])

**Recommended order**

1. `projects`
2. `plan_runs`
3. `plan_run_steps`
4. `documents`
5. `document_versions`
6. `epics` (optional)
7. `stories` (optional)
8. `tasks`

**Notes**

* Add foreign keys with `cascadeOnDelete()` where appropriate:

  * Deleting a project should delete its plan runs, docs, tasks (or soft delete project if you prefer).
* Use `nullable()` FKs for fields that are optional (e.g. `epic_id` on task).
* After your schema stabilizes, consider `php artisan schema:dump --prune` to speed fresh installs for contributors. ([Laravel][5])

---

## A Prism-specific detail that affects your schema choices

When you generate **structured tasks/breakdowns**, Prism’s schema system matters. Prism has a Schemas guide and OpenAI provider notes about required vs nullable fields for structured outputs. ([Prism][6])
This is why storing the result directly as JSON (and/or in normalized tables) is ideal: your output is predictable and doesn’t require parsing.

---

## What I’d implement first (today)

1. Migrations for: `projects`, `plan_runs`, `plan_run_steps`, `documents`, `document_versions`, `tasks`
2. Models + relationships + casts
3. Seed a single project and manually create a `plan_run` record to confirm DB shape
4. Only then wire the first `GeneratePrdJob` to store a `document_version`

If you want, paste your existing migrations/models (even partial), and I’ll refactor them into this structure with Laravel-idiomatic conventions (constraints, indexes, casts, enums) while keeping it minimal enough to ship fast.

[1]: https://laravel.com/docs/12.x/eloquent "Eloquent: Getting Started - Laravel 12.x - The PHP Framework For Web Artisans"
[2]: https://prismphp.com/advanced/rate-limits.html?utm_source=chatgpt.com "Handling Rate Limits - Prism"
[3]: https://laravel.com/docs/12.x/rate-limiting?utm_source=chatgpt.com "Rate Limiting - Laravel 12.x - The PHP Framework For Web ..."
[4]: https://cookbook.openai.com/examples/how_to_handle_rate_limits?utm_source=chatgpt.com "How to handle rate limits"
[5]: https://laravel.com/docs/12.x/migrations "Database: Migrations - Laravel 12.x - The PHP Framework For Web Artisans"
[6]: https://prismphp.com/core-concepts/schemas.html?utm_source=chatgpt.com "Schemas - Prism"
