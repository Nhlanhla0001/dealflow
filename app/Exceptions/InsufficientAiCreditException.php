<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientAiCreditException extends RuntimeException
{
    public function __construct(
        public readonly int $balanceMinor,
        public readonly int $requiredMinor,
    ) {
        parent::__construct('The available AI credit balance is too low for this agent.');
    }
}
