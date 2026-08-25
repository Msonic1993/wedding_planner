<?php

namespace App\Http\Requests\Api\V1\Marketplace;

use App\Domain\Vendor\Enums\VendorCategory;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class SearchVendorsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['category' => ['nullable', Rule::in(VendorCategory::values())], 'voivodeship' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'], 'price_min' => ['nullable', 'numeric', 'min:0'], 'price_max' => ['nullable', 'numeric', 'gte:price_min'], 'date' => ['nullable', 'date'], 'min_rating' => ['nullable', 'numeric', 'min:1', 'max:5'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']];
    }
}
