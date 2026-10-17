<?php

namespace App\Infrastructure\Location;

use App\Application\Location\Contracts\LocationCatalog;
use RuntimeException;

final class GeoNamesJsonLocationCatalog implements LocationCatalog
{
    /** @var array{countries: list<array{code: string, name: string}>, regions: array<string, list<array{code: string, name: string}>>, cities: array<string, list<array{id: int, name: string, asciiName: string, alternateNames: string, regionCode: string, population: int}>>}|null */
    private static ?array $catalog = null;

    public function countries(): array
    {
        return $this->catalog()['countries'];
    }

    public function regions(string $countryCode): array
    {
        return array_map(
            static fn (array $region): array => ['code' => $region['code'], 'name' => $region['name']],
            $this->catalog()['regions'][strtoupper($countryCode)] ?? [],
        );
    }

    public function searchCities(string $countryCode, ?string $regionCode, string $query, int $limit): array
    {
        $normalizedQuery = $this->normalize($query);
        $matches = [];

        foreach ($this->catalog()['cities'][strtoupper($countryCode)] ?? [] as $city) {
            if ($regionCode !== null && $regionCode !== '' && $city['regionCode'] !== $regionCode) {
                continue;
            }

            $normalizedName = $this->normalize($city['name']);
            $normalizedAsciiName = $this->normalize($city['asciiName']);
            $normalizedAlternateNames = $this->normalize($city['alternateNames']);
            if ($normalizedQuery !== '' && ! str_contains($normalizedName, $normalizedQuery) && ! str_contains($normalizedAsciiName, $normalizedQuery) && ! str_contains($normalizedAlternateNames, $normalizedQuery)) {
                continue;
            }

            $matches[] = [
                'id' => $city['id'],
                'name' => $city['name'],
                'country_code' => strtoupper($countryCode),
                'region_code' => $city['regionCode'],
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    public function hasCountry(string $countryCode): bool
    {
        return array_any($this->countries(), static fn (array $country): bool => $country['code'] === strtoupper($countryCode));
    }

    public function hasRegion(string $countryCode, string $regionCode): bool
    {
        return array_any($this->regions($countryCode), static fn (array $region): bool => $region['code'] === $regionCode);
    }

    public function findCity(int $cityId): ?array
    {
        foreach ($this->catalog()['cities'] as $countryCode => $cities) {
            foreach ($cities as $city) {
                if ($city['id'] === $cityId) {
                    return [
                        'id' => $city['id'],
                        'name' => $city['name'],
                        'country_code' => $countryCode,
                        'region_code' => $city['regionCode'],
                    ];
                }
            }
        }

        return null;
    }

    public function findCities(array $cityIds): array
    {
        $cities = [];
        foreach ($cityIds as $cityId) {
            $city = $this->findCity($cityId);
            if ($city !== null) {
                $cities[] = $city;
            }
        }

        return $cities;
    }

    /** @return array{countries: list<array{code: string, name: string}>, regions: array<string, list<array{code: string, name: string}>>, cities: array<string, list<array{id: int, name: string, asciiName: string, alternateNames: string, regionCode: string, population: int}>>} */
    private function catalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $contents = file_get_contents(resource_path('data/geonames-locations.json'));
        if ($contents === false) {
            throw new RuntimeException('The GeoNames location catalog is unavailable.');
        }

        /** @var mixed $decoded */
        $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! isset($decoded['countries'], $decoded['regions'], $decoded['cities'])) {
            throw new RuntimeException('The GeoNames location catalog has an invalid format.');
        }

        /** @var array{countries: list<array{code: string, name: string}>, regions: array<string, list<array{code: string, name: string}>>, cities: array<string, list<array{id: int, name: string, asciiName: string, alternateNames: string, regionCode: string, population: int}>>} $decoded */
        self::$catalog = $decoded;

        return self::$catalog;
    }

    private function normalize(string $value): string
    {
        $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);

        return mb_strtolower($transliterated === false ? $value : $transliterated);
    }
}
