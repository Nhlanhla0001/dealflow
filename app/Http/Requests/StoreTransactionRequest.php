<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBuyer() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('budget') && ! $this->has('budget_minor')) {
            $this->merge([
                'budget_minor' => (int) round(((float) $this->input('budget')) * 100),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Seller->value),
            ],
            'budget_minor' => ['required', 'integer', 'min:100', 'max:100000000'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
