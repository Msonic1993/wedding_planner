<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Favorite\Contracts\FavoriteService;
use App\Domain\Vendor\Models\VendorProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Favorite\FavoritePaginationRequest;
use App\Http\Resources\Api\V1\FavoriteVendorResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class FavoriteController extends Controller
{
    public function __construct(private readonly FavoriteService $favorites) {}

    /**
     * @OA\Get(
     *     path="/api/v1/favorites/vendors",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     summary="List saved vendors",
     *     description="Returns only vendors saved by the authenticated Bride or Groom, newest favorites first. Each entry contains the complete MarketplaceVendor contract, including current average rating and review count. Removing a vendor profile automatically removes its favorite entries.",
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of favorites per page. Defaults to 20; accepted range is 1 through 50.",
     *
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=20, example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Favorite vendor list. items is empty when the account has no favorites.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", nullable=true, example=null),
     *             @OA\Property(property="data", ref="#/components/schemas/FavoriteVendorList"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Sanctum Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="per_page is not an integer from 1 through 50.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(FavoritePaginationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $favorites = $this->favorites->list($user, $request->integer('per_page', 20));

        return $this->success([
            'items' => FavoriteVendorResource::collection($favorites->items()),
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/favorites/vendors/{vendor}",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     summary="Save a vendor as favorite",
     *     description="Idempotently saves a vendor profile for the authenticated Bride or Groom. The request has no body. Repeating the same PUT does not create a duplicate and returns the original favorite with its original favorited_at timestamp. Use the vendor profile ID returned by marketplace search.",
     *
     *     @OA\Parameter(
     *         name="vendor",
     *         in="path",
     *         required=true,
     *         description="Vendor profile ID from MarketplaceVendor.id.",
     *
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vendor is saved. The same response is returned when it was already saved.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vendor has been added to favorites."),
     *             @OA\Property(property="data", ref="#/components/schemas/FavoriteVendor"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Sanctum Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(VendorProfile $vendor): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(
            new FavoriteVendorResource($this->favorites->save($user, $vendor)),
            __('app.favorite.saved'),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/favorites/vendors/{vendor}",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     summary="Remove a vendor from favorites",
     *     description="Idempotently removes the vendor from the authenticated Bride or Groom's favorites. The request has no body. Repeating DELETE after removal still returns 200 so mobile clients can safely reconcile optimistic UI state.",
     *
     *     @OA\Parameter(
     *         name="vendor",
     *         in="path",
     *         required=true,
     *         description="Vendor profile ID from MarketplaceVendor.id.",
     *
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vendor is no longer saved.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vendor has been removed from favorites."),
     *             @OA\Property(property="data", nullable=true, example=null),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Sanctum Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(VendorProfile $vendor): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();
        $this->favorites->remove($user, $vendor);

        return $this->success(message: __('app.favorite.removed'));
    }
}
