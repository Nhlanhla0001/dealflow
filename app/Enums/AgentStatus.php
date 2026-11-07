<?php

namespace App\Enums;

enum AgentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
}
