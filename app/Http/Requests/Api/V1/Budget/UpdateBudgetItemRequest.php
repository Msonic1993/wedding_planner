<?php

namespace App\Http\Requests\Api\V1\Budget;

use App\Domain\Budget\Enums\BudgetCategory;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetItemRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', Rule::in(BudgetCategory::values())],
            'estimated_amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'paid_amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
