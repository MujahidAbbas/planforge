<?php

namespace App\Enums;

enum PlanRunStepStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Delayed = 'delayed';      // Rate-limited or overloaded, will retry
    case Failed = 'failed';
    case Succeeded = 'succeeded';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
}
