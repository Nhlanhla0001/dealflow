<?php

namespace App\Enums;

enum WizardStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
