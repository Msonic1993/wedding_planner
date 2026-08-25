<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Vendor\Contracts\VendorReviewService;
use App\Domain\Vendor\Models\VendorProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketplace\VendorReviewRequest;
use App\Http\Resources\Api\V1\VendorReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class VendorReviewController extends Controller
{
    public function __construct(private readonly VendorReviewService $reviews) {}

    /**
     * @OA\Post(
     *     path="/api/v1/vendors/{vendor}/review",
     *     tags={"Vendor reviews"},
     *     security={{"sanctum":{}}},
     *     summary="Create a review for a vendor",
     *     description="Available only to Bride and Groom accounts. A user can submit exactly one review for each vendor. Use PATCH to change an existing review.",
     *
     *     @OA\Parameter(
     *         name="vendor",
     *         in="path",
     *         required=true,
     *         description="Vendor profile ID returned by marketplace search.",
     *
     *         @OA\Schema(type="integer", format="int64", example=42)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON review payload.",
     *
     *         @OA\JsonContent(
     *             required={"rating"},
     *
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5, description="Whole-star rating from 1 through 5."),
     *             @OA\Property(property="comment", type="string", nullable=true, maxLength=2000, example="Great communication and beautiful photos.", description="Optional public review text. Pass null to clear it when updating.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review created.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Review has been created."),
     *             @OA\Property(property="data", ref="#/components/schemas/VendorReview"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Account does not have Bride or Groom role.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="Current user has already reviewed this vendor.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Invalid rating or comment.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(VendorReviewRequest $request, VendorProfile $vendor): JsonResponse
    {
        $review = $this->reviews->create($request->user(), $vendor, $request->validated());

        return $this->success(new VendorReviewResource($review), __('app.review.created'), Response::HTTP_CREATED);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/vendors/{vendor}/review",
     *     tags={"Vendor reviews"},
     *     security={{"sanctum":{}}},
     *     summary="Update own review for a vendor",
     *     description="Available only to Bride and Groom accounts. Replaces both rating and comment of the authenticated user's existing review.",
     *
     *     @OA\Parameter(name="vendor", in="path", required=true, description="Vendor profile ID.", @OA\Schema(type="integer", format="int64", example=42)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"rating"}, @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4), @OA\Property(property="comment", type="string", nullable=true, maxLength=2000, example="Updated after receiving the album."))),
     *
     *     @OA\Response(response=200, description="Review updated.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Review has been updated."), @OA\Property(property="data", ref="#/components/schemas/VendorReview"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Account does not have Bride or Groom role.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile or own review does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Invalid rating or comment.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(VendorReviewRequest $request, VendorProfile $vendor): JsonResponse
    {
        $review = $this->reviews->update($request->user(), $vendor, $request->validated());

        return $this->success(new VendorReviewResource($review), __('app.review.updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/vendors/{vendor}/review",
     *     tags={"Vendor reviews"},
     *     security={{"sanctum":{}}},
     *     summary="Delete own review for a vendor",
     *     description="Available only to Bride and Groom accounts. Deletes only the review authored by the authenticated user; it cannot delete reviews of other users.",
     *
     *     @OA\Parameter(name="vendor", in="path", required=true, description="Vendor profile ID.", @OA\Schema(type="integer", format="int64", example=42)),
     *
     *     @OA\Response(response=200, description="Review deleted.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Review has been deleted."), @OA\Property(property="data", nullable=true, example=null), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Account does not have Bride or Groom role.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Vendor profile or own review does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(VendorProfile $vendor): JsonResponse
    {
        $this->reviews->delete(request()->user(), $vendor);

        return $this->success(message: __('app.review.deleted'));
    }
}
