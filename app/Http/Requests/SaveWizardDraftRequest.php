<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWizardDraftRequest extends FormRequest
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
            'data' => ['present', 'array'],
            'current_step' => ['required', 'string', 'max:64'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
