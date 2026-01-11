<?php

namespace App\Enums;

enum DocumentType: string
{
    case Prd = 'prd';
    case Tech = 'tech';

    public function label(): string
    {
        return match ($this) {
            self::Prd => 'PRD',
            self::Tech => 'Technical Specification',
        };
    }
}
