<?php

namespace App\Http\Requests;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Service;
use Illuminate\Contracts\Validation\Validator;
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
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('status', ServiceStatus::Published->value),
            ],
            'seller_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', [
                    UserRole::Seller->value,
                    UserRole::Both->value,
                    UserRole::Admin->value,
                ]),
            ],
            'budget_minor' => ['required', 'integer', 'min:100', 'max:100000000'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('service_id')) {
                    return;
                }

                $service = Service::query()->find($this->integer('service_id'));

                if ($service !== null && $service->seller_id !== $this->integer('seller_id')) {
                    $validator->errors()->add(
                        'service_id',
                        'The selected service does not belong to the selected seller.',
                    );
                }
            },
        ];
    }
}
