Perfect — Step 7 is where your app starts to feel “real”. The cleanest, scalable way is:

* Create a **PlanRun** record (+ two **PlanRunStep** rows: `prd`, `tech`)
* Dispatch a **job chain**: `GeneratePrdJob` → `GenerateTechSpecJob`
* Each job:

  * marks its step `running → succeeded/failed`
  * writes a **DocumentVersion**
  * updates the Document’s `current_version_id`

Laravel has first-class **Job Chaining** via `Bus::chain()` (chain stops if a job fails). ([Laravel][1])
Also, dispatch jobs **after DB commit** to avoid workers running before your records exist. ([Laravel][1])

Below is a step-by-step implementation blueprint.

---

## 1) Add document types + step names (enums/constants)

Create enums (or constants) so you don’t scatter magic strings.

```php
// app/Domain/Planning/Enums/PlanStep.php
enum PlanStep: string { case PRD = 'prd'; case TECH = 'tech'; }

// app/Domain/Docs/Enums/DocType.php
enum DocType: string { case PRD = 'prd'; case TECH = 'tech'; }
```

---

## 2) Create a “StartPlanRun” action (single entry point)

This action creates DB state + dispatches the chain.

```php
namespace App\Domain\Planning\Actions;

use App\Models\Project;
use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Domain\Planning\Enums\PlanStep;
use App\Jobs\GeneratePrdJob;
use App\Jobs\GenerateTechSpecJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class StartPlanRun
{
    public function handle(Project $project, int $userId): PlanRun
    {
        return DB::transaction(function () use ($project, $userId) {
            $run = PlanRun::create([
                'project_id' => $project->id,
                'triggered_by' => $userId,
                'status' => 'queued',
                'provider' => $project->preferred_provider ?? 'openai',
                'model' => $project->preferred_model ?? 'gpt-4o-mini',
                'input_snapshot' => [
                    'idea' => $project->idea,
                    'constraints' => $project->constraints,
                ],
            ]);

            // Create steps up-front (helps UI progress + retry logic later)
            foreach ([PlanStep::PRD, PlanStep::TECH] as $step) {
                PlanRunStep::create([
                    'plan_run_id' => $run->id,
                    'step' => $step->value,
                    'status' => 'queued',
                    'attempt' => 0,
                    'provider' => $run->provider,
                    'model' => $run->model,
                ]);
            }

            // Job chaining (runs sequentially; stops if one fails) :contentReference[oaicite:2]{index=2}
            Bus::chain([
                new GeneratePrdJob($run->id),
                new GenerateTechSpecJob($run->id),
            ])
            ->catch(function (Throwable $e) use ($run) {
                // This runs if any job in the chain fails.
                PlanRun::whereKey($run->id)->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                ]);
            })
            ->dispatch()
            ->afterCommit(); // avoid “job processed before transaction committed” :contentReference[oaicite:3]{index=3}

            return $run;
        });
    }
}
```

---

## 3) Implement `GeneratePrdJob` (Prism text generation + version write)

Prism text generation pattern is `Prism::text()->using(...)->withPrompt(...)->asText()`. ([Prism][2])
You can (and should) use **system prompts** and even **Laravel views** for prompts. ([Prism][2])

```php
namespace App\Jobs;

use App\Models\PlanRun;
use App\Models\PlanRunStep;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Domain\Docs\Enums\DocType;
use App\Domain\Planning\Enums\PlanStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRequestTooLargeException;

class GeneratePrdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    public function __construct(public string $planRunId) {}

    public function handle(): void
    {
        $run = PlanRun::query()->with('project')->findOrFail($this->planRunId);
        $step = PlanRunStep::query()
            ->where('plan_run_id', $run->id)
            ->where('step', PlanStep::PRD->value)
            ->firstOrFail();

        $run->update(['status' => 'running', 'started_at' => $run->started_at ?? now()]);
        $step->update(['status' => 'running', 'attempt' => $step->attempt + 1, 'started_at' => now()]);

        try {
            $providerEnum = Provider::from($run->provider); // ensure your stored provider matches Prism enum values
            $prompt = view('prompts.prd.user', ['project' => $run->project])->render();
            $system = view('prompts.prd.system')->render();

            $response = Prism::text()
                ->using($providerEnum, $run->model)
                ->withSystemPrompt($system)
                ->withPrompt($prompt)
                ->asText(); // :contentReference[oaicite:6]{index=6}

            $doc = Document::firstOrCreate([
                'project_id' => $run->project_id,
                'type' => DocType::PRD->value,
            ]);

            $version = DocumentVersion::create([
                'document_id' => $doc->id,
                'plan_run_id' => $run->id,
                'plan_run_step_id' => $step->id,
                'content_md' => $response->text,
            ]);

            $doc->update(['current_version_id' => $version->id]);

            $step->update(['status' => 'succeeded', 'finished_at' => now()]);
        } catch (PrismRateLimitedException $e) {
            // Prism provides rate limit objects (when provider supports headers). :contentReference[oaicite:7]{index=7}
            $step->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'rate_limits' => $e->rateLimits ?? null,
                'finished_at' => now(),
            ]);
            throw $e; // stops chain (by design) :contentReference[oaicite:8]{index=8}
        } catch (PrismProviderOverloadedException|PrismRequestTooLargeException $e) {
            // Prism provider-agnostic exceptions list. :contentReference[oaicite:9]{index=9}
            $step->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        } catch (\Throwable $e) {
            $step->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }
}
```

---

## 4) Implement `GenerateTechSpecJob` (depends on PRD)

Tech should use the **latest PRD version** as context (or a short summary, later).

```php
namespace App\Jobs;

use App\Models\{PlanRun, PlanRunStep, Document, DocumentVersion};
use App\Domain\Docs\Enums\DocType;
use App\Domain\Planning\Enums\PlanStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Enums\Provider;

class GenerateTechSpecJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    public function __construct(public string $planRunId) {}

    public function handle(): void
    {
        $run = PlanRun::query()->with('project')->findOrFail($this->planRunId);
        $step = PlanRunStep::query()
            ->where('plan_run_id', $run->id)
            ->where('step', PlanStep::TECH->value)
            ->firstOrFail();

        $step->update(['status' => 'running', 'attempt' => $step->attempt + 1, 'started_at' => now()]);

        $prdDoc = Document::query()
            ->where('project_id', $run->project_id)
            ->where('type', DocType::PRD->value)
            ->first();

        $prdText = $prdDoc?->currentVersion?->content_md ?? '';

        $providerEnum = Provider::from($run->provider);

        $system = view('prompts.tech.system')->render();
        $prompt = view('prompts.tech.user', [
            'project' => $run->project,
            'prd' => $prdText,
        ])->render();

        $response = Prism::text()
            ->using($providerEnum, $run->model)
            ->withSystemPrompt($system)
            ->withPrompt($prompt)
            ->asText(); // :contentReference[oaicite:10]{index=10}

        $doc = Document::firstOrCreate([
            'project_id' => $run->project_id,
            'type' => DocType::TECH->value,
        ]);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'plan_run_id' => $run->id,
            'plan_run_step_id' => $step->id,
            'content_md' => $response->text,
        ]);

        $doc->update(['current_version_id' => $version->id]);
        $step->update(['status' => 'succeeded', 'finished_at' => now()]);

        // If both steps succeeded, finalize the run
        $run->update(['status' => 'succeeded', 'finished_at' => now()]);
    }
}
```

---

## 5) Prompt files (keep prompts out of jobs)

Prism explicitly supports passing a Laravel view to `withSystemPrompt` and `withPrompt`. ([Prism][2])

Create:

* `resources/views/prompts/prd/system.blade.php`
* `resources/views/prompts/prd/user.blade.php`
* `resources/views/prompts/tech/system.blade.php`
* `resources/views/prompts/tech/user.blade.php`

This keeps jobs clean and makes prompt iteration easy.

---

## 6) Wire “Generate” button in Livewire to start the run

In your Workspace or PRD tab component:

```php
public function generate()
{
    $run = app(\App\Domain\Planning\Actions\StartPlanRun::class)
        ->handle($this->project, auth()->id());

    $this->dispatch('plan-run-started', id: $run->id); // Livewire events :contentReference[oaicite:12]{index=12}
}
```

Your “plan run banner” (wire:poll) can now show progress by reading `plan_run_steps`.

---

## 7) Best-practice knobs to set now

* **Queue after commit**: either set `after_commit => true` on your queue connection, or keep using `->afterCommit()` on dispatch. ([Laravel][1])
* **Chain failures**: rely on chain stop behavior (PRD fail means Tech won’t run). ([Laravel][1])
* **Prism errors**: Prism documents specific exceptions (rate limited, overloaded, too large). ([Prism][3])
* **Streaming later**: Prism supports streaming output, but don’t do it yet for Step 7. ([Prism][4])

---

# What you should implement *right now* (exact order)

1. `StartPlanRun` action (creates run + steps + dispatches chain) ([Laravel][1])
2. `GeneratePrdJob` (writes PRD DocumentVersion) ([Prism][2])
3. `GenerateTechSpecJob` (reads latest PRD + writes Tech DocumentVersion) ([Prism][2])
4. Livewire “Generate” button + plan-run banner polling
5. Minimal prompt views

If you paste your **model names + relationships** (or your migration names), I’ll adapt the code to match your exact schema (IDs, column names, enums) so you can copy/paste it without refactoring.

[1]: https://laravel.com/docs/12.x/queues "Queues - Laravel 12.x - The PHP Framework For Web Artisans"
[2]: https://prismphp.com/core-concepts/text-generation.html "Text Generation | Prism"
[3]: https://prismphp.com/advanced/error-handling.html "Error handling | Prism"
[4]: https://prismphp.com/core-concepts/streaming-output.html?utm_source=chatgpt.com "Streaming Output - Prism"
