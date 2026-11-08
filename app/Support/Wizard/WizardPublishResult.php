<?php

namespace App\Support\Wizard;

final class WizardPublishResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $type,
        public readonly int $id,
        public readonly string $url,
        public readonly array $meta = [],
    ) {}
}
