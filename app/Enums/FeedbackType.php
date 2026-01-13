<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Completeness = 'completeness';
    case Clarity = 'clarity';
    case Technical = 'technical';
    case Stakeholder = 'stakeholder';
    case Overall = 'overall';

    public function label(): string
    {
        return match ($this) {
            self::Completeness => 'Completeness',
            self::Clarity => 'Clarity',
            self::Technical => 'Technical Review',
            self::Stakeholder => 'Stakeholder Ready',
            self::Overall => 'Overall Assessment',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Completeness => 'What\'s missing from this document?',
            self::Clarity => 'What parts are unclear or ambiguous?',
            self::Technical => 'What technical gaps exist?',
            self::Stakeholder => 'What questions will stakeholders ask?',
            self::Overall => 'Rate and suggest improvements',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Completeness => 'clipboard-document-check',
            self::Clarity => 'eye',
            self::Technical => 'cpu-chip',
            self::Stakeholder => 'user-group',
            self::Overall => 'star',
        };
    }
}
