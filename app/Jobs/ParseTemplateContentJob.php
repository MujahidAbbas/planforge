<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class ParseTemplateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $content,
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
        $system = view('prompts.template-parser.system')->render();
        $prompt = view('prompts.template-parser.user', ['content' => $this->content])->render();

        $response = Prism::text()
            ->using(Provider::Anthropic, 'claude-sonnet-4-20250514')
            ->withMaxTokens(2000)
            ->withSystemPrompt($system)
            ->withPrompt($prompt)
            ->withClientOptions(['timeout' => 60])
            ->asText();

        $parsed = $this->parseResponse($response->text);

        Cache::put($this->cacheKey, $parsed, now()->addMinutes(30));
    }

    /**
     * @return array{name: string, description: string, sections: array<int, array{title: string, description: string}>}
     */
    private function parseResponse(string $text): array
    {
        $text = $this->extractJsonFromMarkdown($text);
        $parsed = json_decode($text, true);

        if (! is_array($parsed)) {
            return $this->defaultStructure();
        }

        return [
            'name' => $parsed['name'] ?? 'Parsed Template',
            'description' => $parsed['description'] ?? '',
            'sections' => $this->normalizeSections($parsed['sections'] ?? []),
        ];
    }

    private function extractJsonFromMarkdown(string $text): string
    {
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $matches)) {
            return trim($matches[1]);
        }

        return $text;
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    private function normalizeSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        return array_map(fn (array $section): array => [
            'title' => $section['title'] ?? '',
            'description' => $section['description'] ?? '',
        ], $sections);
    }

    /**
     * @return array{name: string, description: string, sections: array<int, mixed>}
     */
    private function defaultStructure(): array
    {
        return [
            'name' => 'Parsed Template',
            'description' => '',
            'sections' => [],
        ];
    }
}
