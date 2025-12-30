<?php

namespace App\Enums;

enum PlanRunStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Partial = 'partial';
    case Failed = 'failed';
    case Succeeded = 'succeeded';
}
