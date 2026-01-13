<?php

namespace App\Jobs;

use App\Enums\FeedbackType;
use App\Jobs\Concerns\ResolvesAiProvider;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Facades\Prism;

class GenerateFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ResolvesAiProvider, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $documentId,
        public FeedbackType $feedbackType,
        public string $cacheKey,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('llm:requests')];
    }

    public function handle(): void
    {
        $document = Document::with(['currentVersion', 'project'])->findOrFail($this->documentId);
        $project = $document->project;

        $system = view('prompts.feedback.system')->render();
        $prompt = view("prompts.feedback.{$this->feedbackType->value}", [
            'content' => $document->currentVersion->content_md,
            'documentType' => strtolower($document->type->label()),
        ])->render();

        $provider = $this->resolveProvider($project->preferred_provider ?? 'anthropic');
        $model = $project->preferred_model ?? 'claude-sonnet-4-20250514';

        $response = Prism::text()
            ->using($provider, $model)
            ->withMaxTokens(2000)
            ->withSystemPrompt($system)
            ->withPrompt($prompt)
            ->withClientOptions(['timeout' => 60])
            ->asText();

        Cache::put($this->cacheKey, [
            'feedback' => $response->text,
            'type' => $this->feedbackType->value,
            'generated_at' => now()->toIso8601String(),
        ], now()->addHour());
    }
}
