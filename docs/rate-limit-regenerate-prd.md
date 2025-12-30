Nice — these two steps are where your app starts feeling “production” instead of “demo”.

## Step 10: Add rate-limit resilience early

### 10.1 Put **all Prism calls behind queue jobs** (and throttle the jobs)

Your PRD/Tech/Tasks steps are perfect candidates for queued jobs, and Laravel has built-in job middleware for rate limiting. ([Laravel][1])

**Define a queue rate limiter** (AppServiceProvider):

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('llm:requests', function ($job) {
        // Scope by provider+model+workspace/user if you want
        return Limit::perMinute(30)->by('llm:global');
    });
}
```

**Attach middleware to every LLM job**:

```php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware(): array
{
    return [new RateLimited('llm:requests')];
}
```

Laravel’s `RateLimited` middleware will release the job with an appropriate delay when the limit is exceeded. ([Laravel][1])

> Use Redis as the limiter cache for accuracy at scale (`cache.limiter = redis`). ([Laravel][2])

---

### 10.2 Catch Prism’s **rate-limit exception** and “sleep until reset”

Prism throws `PrismRateLimitedException`, and (for many providers) exposes `ProviderRateLimit` objects including `resetsAt`. ([Prism][3])

Inside your job:

```php
use Prism\Prism\Exceptions\PrismRateLimitedException;

try {
   // Prism call...
} catch (PrismRateLimitedException $e) {
    // Pick the soonest reset (or the one you believe you hit)
    $resetAt = collect($e->rateLimits)
        ->map(fn($rl) => $rl->resetsAt)
        ->sort()
        ->first();

    $delaySeconds = max(5, now()->diffInSeconds($resetAt, false));
    
    // Update your PlanRunStep to "delayed" + next_attempt_at, then:
    $this->release($delaySeconds);
    return;
}
```

Prism also includes rate limit info on **successful** responses via `$response->meta->rateLimits`, which lets you do “dynamic rate limiting” (update your own limiter based on real headers). ([Prism][3])

---

### 10.3 Handle providers that **don’t provide rate-limit headers**

Prism notes that **OpenAI, Gemini, xAI, VoyageAI** don’t provide the necessary headers for `ProviderRateLimit` objects. ([Prism][3])
So for those, rely on **your app-side limiter** (10.1) + conservative retries/backoff (10.4).

---

### 10.4 Treat “transient” errors as retryable

Prism documents additional provider-driven exceptions like overload/capacity and request-too-large. ([Prism][4])
Best practice:

* **Overloaded** → release with short exponential backoff (e.g., 10s, 30s, 60s)
* **Too large** → mark step failed with an actionable error (“prompt too long, reduce inputs”)

---

### 10.5 Make your PlanRunStep state machine reflect reality

Add (or enforce) these step statuses:

* `queued` → `running` → `succeeded`
* `delayed` (rate-limited / overloaded; has `next_attempt_at`)
* `failed` (non-retryable)
* `cancelled`

This makes the UI honest and prevents “stuck spinners”.

---

## Step 11: Add “Regenerate PRD / Tech / Tasks” properly

The key is: regeneration should create **new versions** and keep history, not overwrite blindly.

### 11.1 Create “versioned artifacts”

Recommended tables:

* `prd_versions` (project_id, plan_run_id, content, prompt_hash, provider, model, meta)
* `tech_versions`
* `task_sets` (a set record) + `tasks` (cards), where tasks have `source_task_set_id` and maybe `is_user_modified`

Then the project points to the “active” versions:

* `projects.active_prd_version_id`
* `projects.active_tech_version_id`
* `projects.active_task_set_id`

Now “regen PRD” is safe: it creates a new PRD version and you can choose whether to also regenerate downstream artifacts.

---

### 11.2 Dispatch regen pipelines with **job chaining**

Laravel’s `Bus::chain` is perfect for PRD → Tech → Tasks and guarantees order (next runs only after previous succeeds). ([Laravel][1])

Example strategies:

* **Regenerate PRD only**: chain `[GeneratePrdVersion]`
* **Regenerate PRD + downstream**: chain `[GeneratePrdVersion, GenerateTechVersion, GenerateTaskSet]`
* **Regenerate Tech only**: chain `[GenerateTechVersion]` (using current active PRD)
* **Regenerate Tasks only**: chain `[GenerateTaskSet]` (using current active PRD + Tech)

---

### 11.3 Ensure the chain dispatches **after DB commit**

When a user clicks “Regenerate”, you’ll create a PlanRun + PlanRunSteps in a transaction. If a worker runs before commit, you’ll get missing rows.

Laravel supports:

* queue connection config `after_commit => true`, or
* per-dispatch `->afterCommit()` ([Laravel][1])

Use one of these.

---

### 11.4 Prevent “spam-click regen” with **Unique Jobs**

Make each regen step job unique per `(project_id, step_type, plan_run_id)` so multiple clicks don’t enqueue duplicates. Laravel’s `ShouldBeUnique` / `ShouldBeUniqueUntilProcessing` exist for exactly this. ([Laravel][1])

---

### 11.5 Regenerate Tasks without destroying user edits

If you allow users to edit kanban cards, don’t wipe them.

Common pattern:

* AI tasks: `origin = 'ai'`, `task_set_id = X`
* User edits: either

  * mark `is_user_modified = true`, and skip deletion, or
  * copy user-modified tasks into the new task set

MVP-friendly approach:

* “Regenerate Tasks (replace AI tasks)” button → deletes only `origin='ai'` tasks for the active task set, inserts new ones, leaves user-created cards intact.

---

### 11.6 UI/UX that feels pro

Add three buttons in the Workspace:

* **Regenerate PRD** (dropdown: “PRD only” / “PRD + Tech + Tasks”)
* **Regenerate Tech** (uses active PRD)
* **Regenerate Tasks** (uses active PRD + Tech)

And show:

* last generated timestamp + provider/model
* link to “Version history” (select & activate older versions)

---

## What I’d do next (practical order)

1. Add the **queue limiter** (10.1) + middleware on all LLM jobs. ([Laravel][1])
2. Add the **PrismRateLimitedException release-until-reset** handler (10.2). ([Prism][3])
3. Implement **artifact version tables** + active pointers (11.1).
4. Implement regen actions that create a new PlanRun and dispatch a **chain** after commit (11.2–11.3). ([Laravel][1])
5. Add **unique jobs** to stop duplicates (11.4). ([Laravel][1])

If you paste your current tables for `plan_runs`, `plan_run_steps`, and `tasks` (just the columns), I’ll map this into an exact migration plan + the concrete Livewire actions (`regeneratePrd()`, `regenerateTech()`, `regenerateTasks()`) and the job chain wiring.

[1]: https://laravel.com/docs/12.x/queues "Queues - Laravel 12.x - The PHP Framework For Web Artisans"
[2]: https://laravel.com/docs/12.x/rate-limiting "Rate Limiting - Laravel 12.x - The PHP Framework For Web Artisans"
[3]: https://prismphp.com/advanced/rate-limits.html "Handling Rate Limits | Prism"
[4]: https://prismphp.com/advanced/error-handling.html "Error handling | Prism"
