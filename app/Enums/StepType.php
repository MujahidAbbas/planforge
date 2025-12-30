<?php

namespace App\Enums;

enum StepType: string
{
    case Prd = 'prd';
    case Tech = 'tech';
    case Breakdown = 'breakdown';
    case Tasks = 'tasks';
}
