<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Vendor\Contracts\VendorProfileService;
use App\Domain\Vendor\Enums\VendorCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\VendorProfileRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class VendorProfileController extends Controller
{
    public function __construct(private readonly VendorProfileService $profiles) {}

    /** @OA\Get(path="/api/v1/vendor-categories",tags={"Vendors"},summary="List available vendor categories",description="Public endpoint. data is an array of identifiers: photographer, videographer, dj, band, venue, florist, decorations, cake, car, makeup, hairdresser, wedding_planner.",@OA\Response(response=200,description="Structured success response with string[] in data")) */
    public function categories(): JsonResponse
    {
        return $this->success(VendorCategory::values());
    }

    /** @OA\Get(
     *     path="/api/v1/vendor/profile",
     *     tags={"Vendors"},
     *     security={{"sanctum":{}}},summary="Get own vendor profile",description="Requires Vendor role. data contains VendorProfile schema.",
     *
     *     @OA\Response(response=200,description="Vendor profile",
     *
     *     @OA\JsonContent(@OA\Property(property="success",type="boolean"),
     *
     *     @OA\Property(property="message",nullable=true),
     *     @OA\Property(property="data",ref="#/components/schemas/VendorProfile"),
     *     @OA\Property(property="errors",nullable=true))),
     *
     *     @OA\Response(response=401,description="Unauthenticated",ref="#/components/schemas/ErrorResponse"),
     *     @OA\Response(response=403,description="Wrong role",ref="#/components/schemas/ErrorResponse"),
     *     @OA\Response(response=404,description="Profile absent",ref="#/components/schemas/ErrorResponse"))
     */
    public function show(): JsonResponse
    {
        return $this->success(request()->user()->vendorProfile()->firstOrFail());
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vendor/profile",
     *     tags={"Vendors"},
     *     security={{"sanctum":{}}},
     *     summary="Create own vendor profile",
     *
     *     @OA\RequestBody(required=true,
     *
     *         @OA\MediaType(mediaType="multipart/form-data",
     *
     *             @OA\Schema(required={"name","categories"},
     *
     *                 @OA\Property(property="name",type="string",example="Studio Foto Kowalski"),
     *                 @OA\Property(property="description",type="string",example="Reportaże ślubne w całej Polsce."),
     *                 @OA\Property(property="phone",type="string",example="+48500100200"),
     *                 @OA\Property(property="email",type="string",format="email",example="kontakt@studio.pl"),
     *                 @OA\Property(property="website",type="string",format="uri",example="https://studio.pl"),
     *                 @OA\Property(property="voivodeship",type="string",example="mazowieckie"),
     *                 @OA\Property(property="cities",type="array",@OA\Items(type="string",example="Warszawa")),
     *                 @OA\Property(property="categories",type="array",@OA\Items(type="string",example="photographer")),
     *                 @OA\Property(property="images[]",type="string",format="binary"),
     *                 @OA\Property(property="price_list",type="string",format="binary")
     *             ),
     *
     *         )
     *     ),
     *
     *     @OA\Response(response=201,description="Created; data is VendorProfile"),
     *     @OA\Response(response=401,description="Unauthenticated",ref="#/components/schemas/ErrorResponse"),
     *     @OA\Response(response=403,description="Wrong role",ref="#/components/schemas/ErrorResponse"),
     *     @OA\Response(response=409,description="Profile exists",ref="#/components/schemas/ErrorResponse"),
     *     @OA\Response(response=422,description="Validation error",ref="#/components/schemas/ErrorResponse")
     * )
     */
    public function store(VendorProfileRequest $request): JsonResponse
    {
        return $this->success($this->profiles->create($request->user(), $request->validated()), __('app.vendor.created'), 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/vendor/profile", tags={"Vendors"}, security={{"sanctum":{}}}, summary="Update own vendor profile",
     *     description="Uses the same multipart/form-data fields and validation as POST. name and categories remain required.",
     *
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(required={"name","categories"}, @OA\Property(property="name",type="string",example="Studio Foto Kowalski"), @OA\Property(property="categories",type="array",@OA\Items(type="string",example="photographer")), @OA\Property(property="images[]",type="string",format="binary"), @OA\Property(property="price_list",type="string",format="binary")))),
     *
     *     @OA\Response(response=200,description="Updated; data is VendorProfile"), @OA\Response(response=401,description="Unauthenticated",ref="#/components/schemas/ErrorResponse"), @OA\Response(response=403,description="Wrong role",ref="#/components/schemas/ErrorResponse"), @OA\Response(response=404,description="Profile absent",ref="#/components/schemas/ErrorResponse"), @OA\Response(response=422,description="Validation error",ref="#/components/schemas/ErrorResponse")
     * )
     */
    public function update(VendorProfileRequest $request): JsonResponse
    {
        return $this->success($this->profiles->update($request->user(), $request->validated()), __('app.vendor.updated'));
    }
}
