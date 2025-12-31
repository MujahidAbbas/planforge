<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'med';
    case High = 'high';
}
