<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/** @OA\Schema(schema="WeddingProfile",required={"id","first_name","last_name"},@OA\Property(property="id",type="integer"),@OA\Property(property="first_name",type="string",maxLength=100),@OA\Property(property="last_name",type="string",maxLength=100),@OA\Property(property="wedding_date",type="string",format="date",nullable=true),@OA\Property(property="budget",type="string",example="60000.00",nullable=true),@OA\Property(property="guest_count",type="integer",nullable=true),@OA\Property(property="location",type="string",nullable=true),@OA\Property(property="photo_url",type="string",format="uri",nullable=true),@OA\Property(property="created_at",type="string",format="date-time"),@OA\Property(property="updated_at",type="string",format="date-time")) */
final class WeddingProfileSchema {}
