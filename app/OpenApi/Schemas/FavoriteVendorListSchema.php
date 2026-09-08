<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FavoriteVendorList",
 *     required={"items","pagination"},
 *
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/FavoriteVendor")),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         required={"current_page","last_page","per_page","total"},
 *         @OA\Property(property="current_page", type="integer", minimum=1, example=1),
 *         @OA\Property(property="last_page", type="integer", minimum=1, example=3),
 *         @OA\Property(property="per_page", type="integer", minimum=1, maximum=50, example=20),
 *         @OA\Property(property="total", type="integer", minimum=0, example=52)
 *     )
 * )
 */
final class FavoriteVendorListSchema {}
