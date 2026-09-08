<?php

namespace App\Http\Requests\Api\V1\Budget;

use App\Domain\Budget\Enums\BudgetCategory;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(BudgetCategory::values())],
            'estimated_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'paid_amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
