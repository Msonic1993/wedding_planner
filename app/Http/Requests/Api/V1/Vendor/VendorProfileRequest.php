<?php

namespace App\Http\Requests\Api\V1\Vendor;

use App\Domain\Vendor\Enums\VendorCategory;
use App\Http\Requests\Api\V1\ApiRequest;
use App\Http\Requests\Api\V1\Concerns\ValidatesProfileLocation;
use Illuminate\Validation\Rule;

class VendorProfileRequest extends ApiRequest
{
    use ValidatesProfileLocation;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email'], 'website' => ['nullable', 'url'], 'social_media' => ['nullable', 'array'], 'social_media.*' => ['url'], 'country_code' => ['nullable', 'required_with:region_code,city_ids', 'string', 'size:2'], 'region_code' => ['nullable', 'required_with:country_code,city_ids', 'string', 'max:20'], 'city_ids' => ['nullable', 'required_with:country_code,region_code', 'array', 'max:50'], 'city_ids.*' => ['integer', 'distinct'], 'categories' => ['required', 'array', 'min:1'], 'categories.*' => [Rule::in(VendorCategory::values())], 'price_from' => ['nullable', 'numeric', 'min:0'], 'price_to' => ['nullable', 'numeric', 'gte:price_from'], 'available_from' => ['nullable', 'date'], 'available_to' => ['nullable', 'date', 'after_or_equal:available_from'], 'images' => ['nullable', 'array', 'max:10'], 'images.*' => ['image', 'max:5120'], 'price_list' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']];
    }
}
