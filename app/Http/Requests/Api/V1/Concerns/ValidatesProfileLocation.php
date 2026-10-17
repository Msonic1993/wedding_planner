<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Application\Location\Contracts\LocationCatalog;
use Illuminate\Validation\Validator;

trait ValidatesProfileLocation
{
    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $countryCode = strtoupper($this->string('country_code')->toString());
            $regionCode = $this->string('region_code')->toString();
            $cityIds = $this->input('city_ids', $this->filled('city_id') ? [$this->integer('city_id')] : []);
            $cityIds = is_array($cityIds) ? array_map('intval', $cityIds) : [];

            if ($countryCode === '' && $regionCode === '' && $cityIds === []) {
                return;
            }

            $locations = app(LocationCatalog::class);
            if (! $locations->hasCountry($countryCode)) {
                $validator->errors()->add('country_code', __('validation.in', ['attribute' => 'country_code']));

                return;
            }
            if (! $locations->hasRegion($countryCode, $regionCode)) {
                $validator->errors()->add('region_code', __('validation.in', ['attribute' => 'region_code']));

                return;
            }

            $cities = $locations->findCities($cityIds);
            if (count($cities) !== count($cityIds)) {
                $validator->errors()->add($this->has('city_ids') ? 'city_ids' : 'city_id', __('validation.in', ['attribute' => 'city']));

                return;
            }
            foreach ($cities as $city) {
                if ($city['country_code'] !== $countryCode || $city['region_code'] !== $regionCode) {
                    $validator->errors()->add($this->has('city_ids') ? 'city_ids' : 'city_id', __('validation.in', ['attribute' => 'city']));

                    return;
                }
            }
        }];
    }
}
