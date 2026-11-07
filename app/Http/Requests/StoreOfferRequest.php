<?php

namespace App\Http\Requests;

use App\Enums\TransactionState;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            && $transaction->seller_id === $this->user()?->id
            && $transaction->state === TransactionState::Draft;
    }

    protected function prepareForValidation(): void
    {
        foreach (['price', 'floor'] as $field) {
            if ($this->has($field) && ! $this->has($field.'_minor')) {
                $this->merge([
                    $field.'_minor' => (int) round(((float) $this->input($field)) * 100),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price_minor' => ['required', 'integer', 'min:1', 'max:100000000'],
            'floor_minor' => ['required', 'integer', 'min:1', 'lte:price_minor'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'terms' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
