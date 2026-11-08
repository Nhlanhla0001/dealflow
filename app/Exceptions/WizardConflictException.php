<?php

namespace App\Exceptions;

use App\Models\WizardDraft;
use RuntimeException;

class WizardConflictException extends RuntimeException
{
    public function __construct(public readonly WizardDraft $latest)
    {
        parent::__construct('This draft was updated in another session. Reload it and try again.');
    }
}
