<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="VendorInquiry",
 *     required={"id","vendor","couple","requested_date","messages_count","last_message_at","unread_count","created_at","updated_at"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=31),
 *     @OA\Property(property="vendor", type="object", required={"id","name"}, @OA\Property(property="id", type="integer", format="int64", example=42), @OA\Property(property="name", type="string", example="Studio Foto Kowalski")),
 *     @OA\Property(property="couple", type="object", required={"id","name"}, @OA\Property(property="id", type="integer", format="int64", example=7), @OA\Property(property="name", type="string", example="Anna Kowalska")),
 *     @OA\Property(property="requested_date", type="string", format="date", nullable=true, example="2027-06-12"),
 *     @OA\Property(property="messages_count", type="integer", minimum=0, example=2),
 *     @OA\Property(property="last_message_at", type="string", format="date-time", nullable=true, example="2026-09-08T11:00:00.000000Z", description="Time of the newest message; inquiry lists are sorted by this value descending."),
 *     @OA\Property(property="unread_count", type="integer", minimum=0, example=1, description="Messages from the other participant that the authenticated user has not read yet."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-08T10:15:30.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-09-08T11:00:00.000000Z")
 * )
 */
final class VendorInquirySchema {}
