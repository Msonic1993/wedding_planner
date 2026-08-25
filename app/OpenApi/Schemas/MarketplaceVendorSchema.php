<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="MarketplaceVendor",
 *     required={"id","name","cities","categories","rating","reviews_count","image_paths"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=42),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Studio Foto Kowalski"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Wedding photography across Poland."),
 *     @OA\Property(property="website", type="string", format="uri", nullable=true, example="https://studio.example"),
 *     @OA\Property(property="voivodeship", type="string", nullable=true, maxLength=100, example="mazowieckie"),
 *     @OA\Property(property="cities", type="array", @OA\Items(type="string", example="Warszawa")),
 *     @OA\Property(property="categories", type="array", @OA\Items(type="string", example="photographer")),
 *     @OA\Property(property="price_from", type="number", format="float", nullable=true, example=2500),
 *     @OA\Property(property="price_to", type="number", format="float", nullable=true, example=6000),
 *     @OA\Property(property="available_from", type="string", format="date", nullable=true, example="2027-01-01"),
 *     @OA\Property(property="available_to", type="string", format="date", nullable=true, example="2027-12-31"),
 *     @OA\Property(property="rating", type="number", format="float", nullable=true, minimum=1, maximum=5, example=4.75, description="Average rating rounded to two decimal places; null if no reviews exist."),
 *     @OA\Property(property="reviews_count", type="integer", minimum=0, example=12),
 *     @OA\Property(property="image_paths", type="array", @OA\Items(type="string", example="vendor-images/42/cover.jpg"))
 * )
 */
final class MarketplaceVendorSchema {}
