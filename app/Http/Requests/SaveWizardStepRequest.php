<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWizardStepRequest extends FormRequest
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
            'step' => ['required', 'string', 'max:64'],
            'data' => ['present', 'array'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
