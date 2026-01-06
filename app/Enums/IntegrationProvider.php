<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case GitHub = 'github';
    case Jira = 'jira';
    case Trello = 'trello';
    case Linear = 'linear';
}
