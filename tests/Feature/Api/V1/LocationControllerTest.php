<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    public function test_location_catalog_is_public_and_searches_real_cities(): void
    {
        $this->getJson('/api/v1/locations/countries')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['code' => 'PL', 'name' => 'Polska']);

        $regions = $this->getJson('/api/v1/locations/regions?country=PL')
            ->assertOk()
            ->json('data');
        $mazowieckie = collect($regions)->firstWhere('code', '78');
        $this->assertIsArray($mazowieckie);

        $this->getJson('/api/v1/locations/cities?country=PL&region='.$mazowieckie['code'].'&query=Warsz&limit=5')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Warsaw')
            ->assertJsonPath('data.0.country_code', 'PL')
            ->assertJsonPath('data.0.region_code', $mazowieckie['code']);
    }

    public function test_location_queries_validate_country_region_and_limits(): void
    {
        $this->getJson('/api/v1/locations/regions?country=XX')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['country']);

        $this->getJson('/api/v1/locations/cities?country=PL&region=does-not-exist&limit=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['region', 'limit']);
    }
}
