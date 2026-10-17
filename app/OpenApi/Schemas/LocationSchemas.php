<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(schema="Country",required={"code","name"},@OA\Property(property="code",type="string",example="PL"),@OA\Property(property="name",type="string",example="Polska"))
 * @OA\Schema(schema="Region",required={"code","name"},@OA\Property(property="code",type="string",example="78"),@OA\Property(property="name",type="string",example="Mazowieckie"))
 * @OA\Schema(schema="City",required={"id","name","country_code","region_code"},@OA\Property(property="id",type="integer",example=756135),@OA\Property(property="name",type="string",example="Warszawa"),@OA\Property(property="country_code",type="string",example="PL"),@OA\Property(property="region_code",type="string",example="78"))
 */
final class LocationSchemas {}
