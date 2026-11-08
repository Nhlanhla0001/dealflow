<?php

namespace App\Support\Wizard;

final class WizardStep
{
    /**
     * @param  array<string, mixed>  $rules
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly array $rules = [],
        public readonly bool $review = false,
    ) {}

    /**
     * @return array{key: string, label: string, description: string, review: bool}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'review' => $this->review,
        ];
    }
}
