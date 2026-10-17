<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="VendorProfileInput",
 *   required={"name","categories"},
 *
 *   @OA\Property(property="name",type="string",maxLength=255,example="Studio Foto"),
 *   @OA\Property(property="description",type="string",nullable=true),
 *   @OA\Property(property="phone",type="string",maxLength=30,nullable=true),
 *   @OA\Property(property="email",type="string",format="email",nullable=true),
 *   @OA\Property(property="website",type="string",format="uri",nullable=true),
 *   @OA\Property(property="social_media",type="object",nullable=true,additionalProperties=@OA\Schema(type="string",format="uri")),
 *   @OA\Property(property="country_code",type="string",minLength=2,maxLength=2,nullable=true,example="PL"),
 *   @OA\Property(property="region_code",type="string",maxLength=20,nullable=true,example="78"),
 *   @OA\Property(property="city_ids",type="array",maxItems=50,@OA\Items(type="integer"),example={756135}),
 *   @OA\Property(property="categories",type="array",minItems=1,@OA\Items(type="string",enum={"photographer","videographer","dj","band","venue","florist","decorations","cake","car","makeup","hairdresser","wedding_planner"})),
 *   @OA\Property(property="price_from",type="number",minimum=0,nullable=true),
 *   @OA\Property(property="price_to",type="number",minimum=0,nullable=true),
 *   @OA\Property(property="available_from",type="string",format="date",nullable=true),
 *   @OA\Property(property="available_to",type="string",format="date",nullable=true),
 *   @OA\Property(property="images",type="array",maxItems=10,@OA\Items(type="string",format="binary")),
 *   @OA\Property(property="price_list",type="string",format="binary",nullable=true)
 * )
 */
final class VendorProfileInputSchema {}
