<?php

namespace App\Exceptions;

use RuntimeException;

class WizardExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This draft expired. Start a new one to continue.');
    }
}
