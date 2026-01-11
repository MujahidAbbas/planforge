<?php

namespace App\Jobs;

use App\Jobs\Concerns\ResolvesAiProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Providers\ProviderEnum;

class ParseTemplateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ResolvesAiProvider, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $content,
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
        // Build prompts
        $system = view('prompts.template-parser.system')->render();
        $prompt = view('prompts.template-parser.user', [
            'content' => $this->content,
        ])->render();

        // Generate parsed structure using Anthropic
        $response = Prism::text()
            ->using(ProviderEnum::Anthropic, 'claude-sonnet-4-20250514')
            ->withMaxTokens(2000)
            ->withSystemPrompt($system)
            ->withPrompt($prompt)
            ->withClientOptions(['timeout' => 60])
            ->asText();

        // Parse JSON response
        $text = $response->text;

        // Extract JSON from response (handle markdown code blocks)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $parsed = json_decode($text, true);

        // Ensure valid structure
        if (! is_array($parsed)) {
            $parsed = [
                'name' => 'Parsed Template',
                'description' => '',
                'sections' => [],
            ];
        }

        // Normalize sections
        if (isset($parsed['sections']) && is_array($parsed['sections'])) {
            $parsed['sections'] = array_map(function ($section) {
                return [
                    'title' => $section['title'] ?? '',
                    'description' => $section['description'] ?? '',
                ];
            }, $parsed['sections']);
        } else {
            $parsed['sections'] = [];
        }

        // Cache the result for 30 minutes
        Cache::put($this->cacheKey, $parsed, now()->addMinutes(30));
    }
}
