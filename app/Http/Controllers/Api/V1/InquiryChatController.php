<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Chat\Contracts\InquiryChatService;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\ChatMessagesRequest;
use App\Http\Requests\Api\V1\Chat\ChatPaginationRequest;
use App\Http\Requests\Api\V1\Chat\CreateInquiryRequest;
use App\Http\Requests\Api\V1\Chat\MarkChatReadRequest;
use App\Http\Requests\Api\V1\Chat\SendChatMessageRequest;
use App\Http\Resources\Api\V1\ChatMessageResource;
use App\Http\Resources\Api\V1\VendorInquiryResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class InquiryChatController extends Controller
{
    public function __construct(private readonly InquiryChatService $chat) {}

    /**
     * @OA\Post(
     *     path="/api/v1/vendors/{vendor}/inquiries",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Start an availability inquiry with a vendor",
     *     description="Only Bride and Groom roles may create inquiries. It creates a chat thread and its first message atomically. One account can have one thread with a given vendor; use the chat message endpoint for subsequent messages. requested_date is an availability question only and does not create a booking.",
     *
     *     @OA\Parameter(name="vendor", in="path", required=true, description="Vendor profile ID from marketplace search.", @OA\Schema(type="integer", format="int64", example=42)),
     *
     *     @OA\RequestBody(required=true, description="Initial inquiry message in JSON.", @OA\JsonContent(required={"body"}, @OA\Property(property="body", type="string", minLength=1, maxLength=5000, example="Hello, are you available on 12 June 2027?"), @OA\Property(property="requested_date", type="string", format="date", nullable=true, example="2027-06-12", description="Optional requested event date in YYYY-MM-DD format; it cannot be in the past."))),
     *
     *     @OA\Response(response=201, description="Inquiry and its initial message were created.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Inquiry has been created."), @OA\Property(property="data", ref="#/components/schemas/VendorInquiry"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="The account already has an inquiry thread with this vendor.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="body is absent, exceeds 5000 characters, or requested_date is invalid.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(CreateInquiryRequest $request, VendorProfile $vendor): JsonResponse
    {
        $inquiry = $this->chat->createInquiry($request->user(), $vendor, $request->validated());

        return $this->success(new VendorInquiryResource($inquiry), __('app.chat.inquiry_created'), Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chat/inquiries",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="List own inquiry threads",
     *     description="Bride and Groom receive threads they created. Vendor receives threads addressed to vendor profiles owned by that account. Results are newest activity first. No user can see another participant's threads.",
     *
     *     @OA\Parameter(name="page", in="query", description="Page number; defaults to 1.", @OA\Schema(type="integer", minimum=1, default=1, example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page; default 20, minimum 1, maximum 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=20, example=20)),
     *
     *     @OA\Response(response=200, description="Paginated inquiry list.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", ref="#/components/schemas/VendorInquiryList"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user has none of Bride, Groom, Vendor roles.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="per_page is outside the 1–100 range or is not an integer.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(ChatPaginationRequest $request): JsonResponse
    {
        $inquiries = $this->chat->listInquiries($request->user(), $request->integer('per_page', 20));

        return $this->success([
            'items' => VendorInquiryResource::collection($inquiries->items()),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chat/inquiries/{inquiry}",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Get one inquiry thread",
     *     description="Returns thread context only to its couple participant or the owner of the addressed vendor profile. This endpoint supports direct links and screen refreshes without relying on client cache.",
     *
     *     @OA\Parameter(name="inquiry", in="path", required=true, description="Inquiry thread ID.", @OA\Schema(type="integer", format="int64", example=31)),
     *
     *     @OA\Response(response=200, description="Inquiry thread context.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", ref="#/components/schemas/VendorInquiry"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user is not a participant of the inquiry.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Inquiry does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(VendorInquiry $inquiry): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(new VendorInquiryResource($this->chat->getInquiry($user, $inquiry)));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chat/inquiries/{inquiry}/messages",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Read messages from an inquiry thread",
     *     description="Only the couple who created the inquiry and the owner of the addressed vendor profile may read the thread. Items are always returned oldest first. Two modes are supported. Page mode (default, `page`): page 1 contains the newest message window and the response contains `pagination` (ChatMessageList). Cursor mode (`before_id` or `after_id`): keyset pagination without COUNT, recommended under load; the response contains `cursor` (ChatMessageCursorList). Use `before_id` = oldest loaded message ID to prepend older history, and `after_id` = newest known message ID to catch up after a WebSocket reconnect or as a polling fallback; repeat `after_id` requests while `cursor.has_more` is true. New messages are pushed in real time as `chat.message.sent` on private channels `chat.inquiry.{inquiry}` and `chat.user.{userId}` (authorize via POST /api/v1/broadcasting/auth); clients must de-duplicate messages by `id`.",
     *
     *     @OA\Parameter(name="inquiry", in="path", required=true, description="Inquiry thread ID returned by GET /chat/inquiries.", @OA\Schema(type="integer", format="int64", example=31)),
     *     @OA\Parameter(name="page", in="query", description="Page mode only: page number; defaults to 1. Cannot be combined with before_id or after_id.", @OA\Schema(type="integer", minimum=1, default=1, example=1)),
     *     @OA\Parameter(name="before_id", in="query", description="Cursor mode: return the newest messages with an ID lower than this value. Cannot be combined with page or after_id.", @OA\Schema(type="integer", format="int64", minimum=1, example=120)),
     *     @OA\Parameter(name="after_id", in="query", description="Cursor mode: return the oldest messages with an ID greater than this value. Cannot be combined with page or before_id.", @OA\Schema(type="integer", format="int64", minimum=0, example=145)),
     *     @OA\Parameter(name="per_page", in="query", description="Messages per page; default 50, minimum 1, maximum 100.", @OA\Schema(type="integer", minimum=1, maximum=100, default=50, example=50)),
     *
     *     @OA\Response(response=200, description="Messages sorted oldest first. ChatMessageList in page mode, ChatMessageCursorList in cursor mode.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", oneOf={@OA\Schema(ref="#/components/schemas/ChatMessageList"), @OA\Schema(ref="#/components/schemas/ChatMessageCursorList")}), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user is not a participant of the inquiry.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Inquiry does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Invalid page, per_page, before_id or after_id, or page and cursor parameters were combined.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function messages(ChatMessagesRequest $request, VendorInquiry $inquiry): JsonResponse
    {
        $perPage = $request->integer('per_page', 50);

        if ($request->usesCursor()) {
            $window = $this->chat->listMessagesWindow(
                $request->user(),
                $inquiry,
                $request->filled('before_id') ? $request->integer('before_id') : null,
                $request->filled('after_id') ? $request->integer('after_id') : null,
                $perPage,
            );

            return $this->success([
                'items' => ChatMessageResource::collection($window->messages),
                'cursor' => [
                    'per_page' => $perPage,
                    'has_more' => $window->hasMore,
                ],
            ]);
        }

        $messages = $this->chat->listMessages($request->user(), $inquiry, $perPage);
        $orderedMessages = collect($messages->items())->sortBy('id')->values();

        return $this->success([
            'items' => ChatMessageResource::collection($orderedMessages),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chat/inquiries/{inquiry}/messages",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Send a message in an inquiry thread",
     *     description="Only a conversation participant can send a message. The sender is always derived from the Sanctum token; do not send sender_id. This endpoint is used by both the couple and the vendor to continue the internal chat. After commit the message is broadcast as `chat.message.sent` to the other participant's sockets; send the `X-Socket-ID` header to exclude the sender's own connection. Limited to 30 messages per minute per user.",
     *
     *     @OA\Parameter(name="inquiry", in="path", required=true, description="Inquiry thread ID.", @OA\Schema(type="integer", format="int64", example=31)),
     *
     *     @OA\RequestBody(required=true, description="JSON message payload.", @OA\JsonContent(required={"body"}, @OA\Property(property="body", type="string", minLength=1, maxLength=5000, example="Yes, that date is currently available."))),
     *
     *     @OA\Response(response=201, description="Message created.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Message has been sent."), @OA\Property(property="data", ref="#/components/schemas/ChatMessage"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user is not a participant of the inquiry.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Inquiry does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="body is absent or exceeds 5000 characters.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=429, description="Message rate limit exceeded; retry after the number of seconds in the Retry-After header.", @OA\JsonContent(@OA\Property(property="message", type="string", example="Too Many Attempts.")))
     * )
     */
    public function send(SendChatMessageRequest $request, VendorInquiry $inquiry): JsonResponse
    {
        $message = $this->chat->sendMessage($request->user(), $inquiry, $request->validated());

        return $this->success(new ChatMessageResource($message), __('app.chat.message_sent'), Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/chat/inquiries/{inquiry}/read",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Mark a thread as read up to a message",
     *     description="Moves the caller's read marker to last_read_message_id (normally the newest message visible on screen). Markers only move forward: an older ID is accepted but changes nothing. unread_count counts the other participant's messages after the marker. Other devices of the caller receive `chat.unread.updated` on `chat.user.{userId}`; send `X-Socket-ID` to exclude the calling connection. Sending a message marks the thread as read for the sender automatically.",
     *
     *     @OA\Parameter(name="inquiry", in="path", required=true, description="Inquiry thread ID.", @OA\Schema(type="integer", format="int64", example=31)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"last_read_message_id"}, @OA\Property(property="last_read_message_id", type="integer", format="int64", minimum=1, example=145, description="ID of a message belonging to this thread."))),
     *
     *     @OA\Response(response=200, description="Current read state.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", ref="#/components/schemas/ChatReadState"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user is not a participant of the inquiry.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Inquiry does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="last_read_message_id is absent, not an integer, or does not belong to this thread.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function read(MarkChatReadRequest $request, VendorInquiry $inquiry): JsonResponse
    {
        $state = $this->chat->markAsRead($request->user(), $inquiry, $request->integer('last_read_message_id'));

        return $this->success([
            'inquiry_id' => $state->inquiryId,
            'unread_count' => $state->unreadCount,
            'total' => $state->total,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/chat/unread-count",
     *     tags={"Inquiries and chat"},
     *     security={{"sanctum":{}}},
     *     summary="Total number of unread chat messages",
     *     description="Sum of unread messages across all threads of the caller; use it for the tab and app icon badge on start and when the app returns to foreground. Live changes arrive as `chat.unread.updated` with the same `total`.",
     *
     *     @OA\Response(response=200, description="Unread total.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", type="object", required={"total"}, @OA\Property(property="total", type="integer", minimum=0, example=3)), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user has none of Bride, Groom, Vendor roles.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(['total' => $this->chat->unreadTotal($user)]);
    }
}
