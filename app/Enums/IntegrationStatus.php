<?php

namespace App\Enums;

enum IntegrationStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Error = 'error';
    case Disabled = 'disabled';
}
