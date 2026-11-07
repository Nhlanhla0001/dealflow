<?php

namespace App\Support\Wizard;

use App\Models\WizardDraft;

interface WizardDefinition
{
    public function type(): string;

    public function title(): string;

    public function description(): string;

    /**
     * @return array<int, WizardStep>
     */
    public function steps(): array;

    /**
     * @return array<int, string>
     */
    public function stepKeys(): array;

    public function step(string $key): WizardStep;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateStep(string $key, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function completedSteps(array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function startData(): array;

    public function finalize(WizardDraft $draft): mixed;
}
