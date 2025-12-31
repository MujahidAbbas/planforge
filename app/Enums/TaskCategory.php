<?php

namespace App\Enums;

enum TaskCategory: string
{
    case Backend = 'backend';
    case Frontend = 'frontend';
    case Database = 'db';
    case Infrastructure = 'infra';
    case Tests = 'tests';
    case Docs = 'docs';
}
