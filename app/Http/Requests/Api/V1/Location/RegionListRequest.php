<?php

namespace App\Http\Requests\Api\V1\Location;

use App\Application\Location\Contracts\LocationCatalog;
use App\Http\Requests\Api\V1\ApiRequest;
use Closure;

class RegionListRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'size:2', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(LocationCatalog::class)->hasCountry((string) $value)) {
                    $fail(__('validation.in', ['attribute' => $attribute]));
                }
            }],
        ];
    }
}
