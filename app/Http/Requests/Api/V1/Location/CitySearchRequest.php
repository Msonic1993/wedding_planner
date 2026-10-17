<?php

namespace App\Http\Requests\Api\V1\Location;

use App\Application\Location\Contracts\LocationCatalog;
use Closure;

class CitySearchRequest extends RegionListRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'region' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, Closure $fail): void {
                $country = $this->string('country')->toString();
                if ($country !== '' && ! app(LocationCatalog::class)->hasRegion($country, (string) $value)) {
                    $fail(__('validation.in', ['attribute' => $attribute]));
                }
            }],
            'query' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
