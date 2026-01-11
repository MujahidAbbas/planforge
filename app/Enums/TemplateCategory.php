<?php

namespace App\Enums;

enum TemplateCategory: string
{
    case Core = 'core';
    case Technical = 'technical';
    case Strategy = 'strategy';
    case Research = 'research';
    case Analysis = 'analysis';
    case Community = 'community';

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Core Templates',
            self::Technical => 'Technical & Product Documentation',
            self::Strategy => 'Product Planning & Strategy',
            self::Research => 'Research, Testing & UX',
            self::Analysis => 'Analysis & Reporting',
            self::Community => 'Community Templates',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Core => '📄',
            self::Technical => '🔧',
            self::Strategy => '🎯',
            self::Research => '🔬',
            self::Analysis => '📊',
            self::Community => '👥',
        };
    }
}
