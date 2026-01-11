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

    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $documentId,
        public FeedbackType $feedbackType,
        public string $cacheKey,
    ) {
        $this->afterCommit();
    }

    public function middleware(): array
    {
        return [new RateLimited('llm:requests')];
    }

    public function handle(): void
    {
        $document = Document::with(['currentVersion', 'project'])->findOrFail($this->documentId);
        $project = $document->project;

        // Get content
        $content = $document->currentVersion->content_md;
        $documentType = $document->type->label();

        // Build prompts
        $system = view('prompts.feedback.system')->render();
        $prompt = view("prompts.feedback.{$this->feedbackType->value}", [
            'content' => $content,
            'documentType' => strtolower($documentType),
        ])->render();

        // Resolve AI provider
        $providerEnum = $this->resolveProvider($project->preferred_provider ?? 'anthropic');

        // Generate feedback
        $response = Prism::text()
            ->using($providerEnum, $project->preferred_model ?? 'claude-sonnet-4-20250514')
            ->withMaxTokens(2000)
            ->withSystemPrompt($system)
            ->withPrompt($prompt)
            ->withClientOptions(['timeout' => 60])
            ->asText();

        // Cache the result for 1 hour
        Cache::put($this->cacheKey, [
            'feedback' => $response->text,
            'type' => $this->feedbackType->value,
            'generated_at' => now()->toIso8601String(),
        ], now()->addHour());
    }
}
