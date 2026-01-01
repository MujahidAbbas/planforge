<?php

namespace App\Enums;

use Prism\Prism\Enums\Provider;

enum AiProvider: string
{
    case Anthropic = 'anthropic';
    case OpenAI = 'openai';
    case Gemini = 'gemini';
    case Mistral = 'mistral';
    case Groq = 'groq';
    case DeepSeek = 'deepseek';
    case Ollama = 'ollama';
    case OpenRouter = 'openrouter';

    public function toPrismProvider(): Provider
    {
        return match ($this) {
            self::Anthropic => Provider::Anthropic,
            self::OpenAI => Provider::OpenAI,
            self::Gemini => Provider::Gemini,
            self::Mistral => Provider::Mistral,
            self::Groq => Provider::Groq,
            self::DeepSeek => Provider::DeepSeek,
            self::Ollama => Provider::Ollama,
            self::OpenRouter => Provider::OpenRouter,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Anthropic => 'Anthropic',
            self::OpenAI => 'OpenAI',
            self::Gemini => 'Google Gemini',
            self::Mistral => 'Mistral AI',
            self::Groq => 'Groq',
            self::DeepSeek => 'DeepSeek',
            self::Ollama => 'Ollama (Local)',
            self::OpenRouter => 'OpenRouter',
        };
    }

    public function configKey(): string
    {
        return "prism.providers.{$this->value}.api_key";
    }

    public function isLocal(): bool
    {
        return $this === self::Ollama;
    }
}
