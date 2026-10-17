<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ChatReadState",
 *     required={"inquiry_id","unread_count","total"},
 *     description="Read state after marking a thread as read. Same shape as the `chat.unread.updated` WebSocket payload.",
 *
 *     @OA\Property(property="inquiry_id", type="integer", format="int64", example=31),
 *     @OA\Property(property="unread_count", type="integer", minimum=0, example=0, description="Unread messages left in this thread."),
 *     @OA\Property(property="total", type="integer", minimum=0, example=2, description="Unread messages across all threads of the user.")
 * )
 */
final class ChatReadStateSchema {}
