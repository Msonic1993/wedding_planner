<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="VendorReview",
 *     required={"id","rating","comment","created_at","updated_at"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=15),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *     @OA\Property(property="comment", type="string", nullable=true, maxLength=2000, example="Great communication and beautiful photos."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-08-25T12:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-08-25T12:30:00.000000Z")
 * )
 */
final class VendorReviewSchema {}
