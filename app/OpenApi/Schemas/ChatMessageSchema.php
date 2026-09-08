<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ChatMessage",
 *     required={"id","body","sender","created_at"},
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=101),
 *     @OA\Property(property="body", type="string", maxLength=5000, example="Thank you. Is 12 June 2027 available?"),
 *     @OA\Property(property="sender", type="object", required={"id","name"}, @OA\Property(property="id", type="integer", format="int64", example=7), @OA\Property(property="name", type="string", example="Anna Kowalska")),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-08T10:15:30.000000Z")
 * )
 */
final class ChatMessageSchema {}
