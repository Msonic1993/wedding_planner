<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="FavoriteVendor",
 *     required={"id","vendor","favorited_at"},
 *     description="One saved vendor entry owned by the authenticated Bride or Groom account.",
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=73, description="Favorite record identifier. Use vendor.id, not this value, in PUT and DELETE URLs."),
 *     @OA\Property(property="vendor", ref="#/components/schemas/MarketplaceVendor"),
 *     @OA\Property(property="favorited_at", type="string", format="date-time", example="2026-09-08T13:45:00.000000Z", description="UTC timestamp when the vendor was first saved.")
 * )
 */
final class FavoriteVendorSchema {}
