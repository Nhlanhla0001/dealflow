<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm' => ['required', 'accepted'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
