<?php

namespace App\Application\Location\Contracts;

interface LocationCatalog
{
    /** @return list<array{code: string, name: string}> */
    public function countries(): array;

    /** @return list<array{code: string, name: string}> */
    public function regions(string $countryCode): array;

    /** @return list<array{id: int, name: string, country_code: string, region_code: string}> */
    public function searchCities(string $countryCode, ?string $regionCode, string $query, int $limit): array;

    public function hasCountry(string $countryCode): bool;

    public function hasRegion(string $countryCode, string $regionCode): bool;

    /** @return array{id: int, name: string, country_code: string, region_code: string}|null */
    public function findCity(int $cityId): ?array;

    /** @param list<int> $cityIds
     * @return list<array{id: int, name: string, country_code: string, region_code: string}>
     */
    public function findCities(array $cityIds): array;
}
