<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/** @OA\Schema(schema="WeddingProfile",required={"id","first_name","last_name"},@OA\Property(property="id",type="integer"),@OA\Property(property="first_name",type="string",maxLength=100),@OA\Property(property="last_name",type="string",maxLength=100),@OA\Property(property="wedding_date",type="string",format="date",nullable=true),@OA\Property(property="budget",type="string",example="60000.00",nullable=true),@OA\Property(property="guest_count",type="integer",nullable=true),@OA\Property(property="location",type="string",nullable=true),@OA\Property(property="country_code",type="string",nullable=true,example="PL"),@OA\Property(property="region_code",type="string",nullable=true,example="78"),@OA\Property(property="city_id",type="integer",nullable=true,example=756135),@OA\Property(property="photo_url",type="string",format="uri",nullable=true),@OA\Property(property="avatar_key",type="string",nullable=true,enum={"amethyst","bloom","cedar","clementine","dune","fern","iris","juniper","luna","olive","sienna","sky"}),@OA\Property(property="created_at",type="string",format="date-time"),@OA\Property(property="updated_at",type="string",format="date-time")) */
final class WeddingProfileSchema {}
