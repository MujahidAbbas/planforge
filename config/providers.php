<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Model Configuration
    |--------------------------------------------------------------------------
    |
    | Curated model lists for each AI provider. These are displayed in the
    | provider selection UI. Users can also enter custom model IDs.
    |
    */

    'anthropic' => [
        'models' => [
            'claude-sonnet-4-20250514' => 'Claude Sonnet 4 (Recommended)',
            'claude-opus-4-5-20250514' => 'Claude Opus 4.5',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Fast)',
        ],
        'default' => 'claude-sonnet-4-20250514',
    ],

    'openai' => [
        'models' => [
            'gpt-4o' => 'GPT-4o (Recommended)',
            'gpt-4o-mini' => 'GPT-4o Mini (Fast)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'o1' => 'O1 (Reasoning)',
            'o1-mini' => 'O1 Mini',
        ],
        'default' => 'gpt-4o',
    ],

    'gemini' => [
        'models' => [
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Recommended)',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast)',
        ],
        'default' => 'gemini-2.0-flash-exp',
    ],

    'mistral' => [
        'models' => [
            'mistral-large-latest' => 'Mistral Large (Recommended)',
            'mistral-small-latest' => 'Mistral Small',
            'codestral-latest' => 'Codestral',
        ],
        'default' => 'mistral-large-latest',
    ],

    'groq' => [
        'models' => [
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Recommended)',
            'llama-3.1-70b-versatile' => 'Llama 3.1 70B',
            'mixtral-8x7b-32768' => 'Mixtral 8x7B',
        ],
        'default' => 'llama-3.3-70b-versatile',
    ],

    'deepseek' => [
        'models' => [
            'deepseek-chat' => 'DeepSeek Chat (Recommended)',
            'deepseek-reasoner' => 'DeepSeek Reasoner',
        ],
        'default' => 'deepseek-chat',
    ],

    'ollama' => [
        'models' => [
            'llama3.2' => 'Llama 3.2',
            'qwen2.5-coder' => 'Qwen 2.5 Coder',
            'mistral' => 'Mistral',
            'codellama' => 'Code Llama',
        ],
        'default' => 'llama3.2',
    ],

    'openrouter' => [
        'models' => [
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
            'openai/gpt-4o' => 'GPT-4o',
            'google/gemini-pro-1.5' => 'Gemini 1.5 Pro',
            'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B',
        ],
        'default' => 'anthropic/claude-3.5-sonnet',
    ],
];
