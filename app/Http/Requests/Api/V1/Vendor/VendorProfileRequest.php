<?php

namespace App\Http\Requests\Api\V1\Vendor;

use App\Domain\Vendor\Enums\VendorCategory;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class VendorProfileRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email'], 'website' => ['nullable', 'url'], 'social_media' => ['nullable', 'array'], 'social_media.*' => ['url'], 'voivodeship' => ['nullable', 'string', 'max:100'], 'cities' => ['nullable', 'array', 'max:50'], 'cities.*' => ['string', 'max:100'], 'categories' => ['required', 'array', 'min:1'], 'categories.*' => [Rule::in(VendorCategory::values())], 'images' => ['nullable', 'array', 'max:10'], 'images.*' => ['image', 'max:5120'], 'price_list' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']];
    }
}
