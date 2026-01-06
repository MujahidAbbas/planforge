<?php

namespace App\Enums;

enum ExternalLinkSyncStatus: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case Failed = 'failed';
    case Orphaned = 'orphaned';
    case Conflict = 'conflict';
}
