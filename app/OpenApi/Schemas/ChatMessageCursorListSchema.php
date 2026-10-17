<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ChatMessageCursorList",
 *     required={"items","cursor"},
 *     description="Keyset message window returned when before_id or after_id is used. Items are sorted oldest first.",
 *
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/ChatMessage")),
 *     @OA\Property(
 *         property="cursor",
 *         type="object",
 *         required={"per_page","has_more"},
 *         @OA\Property(property="per_page", type="integer", minimum=1, maximum=100, example=50),
 *         @OA\Property(property="has_more", type="boolean", example=true, description="For before_id: older messages exist. For after_id: more newer messages exist; request again with the last returned ID.")
 *     )
 * )
 */
final class ChatMessageCursorListSchema {}
