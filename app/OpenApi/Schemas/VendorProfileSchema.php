<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/** @OA\Schema(schema="VendorProfile",required={"id","name","categories"},@OA\Property(property="id",type="integer"),@OA\Property(property="name",type="string",maxLength=255),@OA\Property(property="description",type="string",nullable=true),@OA\Property(property="phone",type="string",nullable=true),@OA\Property(property="email",type="string",format="email",nullable=true),@OA\Property(property="website",type="string",format="uri",nullable=true),@OA\Property(property="social_media",type="object",nullable=true,additionalProperties=@OA\Schema(type="string",format="uri")),@OA\Property(property="voivodeship",type="string",nullable=true),@OA\Property(property="cities",type="array",@OA\Items(type="string")),@OA\Property(property="categories",type="array",@OA\Items(type="string",enum={"photographer","videographer","dj","band","venue","florist","decorations","cake","car","makeup","hairdresser","wedding_planner"})),@OA\Property(property="image_paths",type="array",@OA\Items(type="string")),@OA\Property(property="price_list_path",type="string",nullable=true)) */
final class VendorProfileSchema {}
