<?php

namespace App\Support\Ai;

class ProviderCatalog
{
    /**
     * @return array<string, array{label: string, configured: bool, models: array<int, array{id: string, label: string}>}>
     */
    public function providers(): array
    {
        $geminiModel = (string) config(
            'ai.providers.gemini.models.text.default',
            'gemini-3-flash-preview',
        );

        return [
            'gemini' => [
                'label' => 'Google Gemini',
                'configured' => filled(config('ai.providers.gemini.key')),
                'models' => [
                    [
                        'id' => $geminiModel,
                        'label' => 'Gemini 3 Flash',
                    ],
                ],
            ],
        ];
    }

    public function isConfigured(string $provider): bool
    {
        return (bool) ($this->providers()[$provider]['configured'] ?? false);
    }

    public function supports(string $provider, string $model): bool
    {
        $models = $this->providers()[$provider]['models'] ?? [];

        return in_array($model, array_column($models, 'id'), true);
    }

    public function defaultModel(string $provider): ?string
    {
        return $this->providers()[$provider]['models'][0]['id'] ?? null;
    }
}
