<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Notification\Contracts\PushDeviceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\RegisterPushDeviceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class PushDeviceController extends Controller
{
    public function __construct(private readonly PushDeviceService $devices) {}

    /**
     * @OA\Post(
     *     path="/api/v1/devices",
     *     tags={"Push notifications"},
     *     security={{"sanctum":{}}},
     *     summary="Register an Expo push token",
     *     description="Registers the device of the authenticated Bride, Groom or Vendor for chat push notifications. Idempotent: call it after every login and whenever Expo returns a new token. A token already registered by another account is moved to the current account, so a shared phone notifies only the signed-in user.",
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"token","platform"}, @OA\Property(property="token", type="string", maxLength=255, pattern="^Expo(nent)?PushToken\[[A-Za-z0-9_-]+\]$", example="ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"), @OA\Property(property="platform", type="string", enum={"ios","android"}, example="ios"))),
     *
     *     @OA\Response(response=200, description="Device registered or refreshed.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Device has been registered."), @OA\Property(property="data", type="object", required={"token","platform"}, @OA\Property(property="token", type="string", example="ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"), @OA\Property(property="platform", type="string", enum={"ios","android"}, example="ios")), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user has none of Bride, Groom, Vendor roles.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="token is not an Expo push token or platform is not ios/android.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(RegisterPushDeviceRequest $request): JsonResponse
    {
        $device = $this->devices->register($request->user(), $request->string('token')->value(), $request->string('platform')->value());

        return $this->success(['token' => $device->token, 'platform' => $device->platform], __('app.device.registered'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/devices/{token}",
     *     tags={"Push notifications"},
     *     security={{"sanctum":{}}},
     *     summary="Unregister an Expo push token",
     *     description="Call before logout so the device stops receiving notifications for this account. Only the owner of the token can remove it. URL-encode the token, e.g. ExponentPushToken%5Bxxx%5D.",
     *
     *     @OA\Parameter(name="token", in="path", required=true, description="URL-encoded Expo push token.", @OA\Schema(type="string", example="ExponentPushToken%5Bxxxxxxxxxxxxxxxxxxxxxx%5D")),
     *
     *     @OA\Response(response=200, description="Device removed.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Device has been removed."), @OA\Property(property="data", nullable=true, example=null), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated user has none of Bride, Groom, Vendor roles.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="The current account has no device with this token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->devices->unregister($user, $token)) {
            return self::errorResponse(__('app.errors.not_found'), Response::HTTP_NOT_FOUND);
        }

        return $this->success(message: __('app.device.removed'));
    }
}
