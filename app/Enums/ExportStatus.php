<?php

namespace App\Enums;

enum ExportStatus: string
{
    case Building = 'building';
    case Ready = 'ready';
    case Failed = 'failed';
}
