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

    /**
     * @OA\Get(
     *   path="/api/v1/vendor-categories", tags={"Vendors"}, summary="List vendor categories",
     *   description="Public endpoint. Use returned identifiers in categories[] when creating a vendor profile.",
     *
     *   @OA\Response(response=200, description="Available categories", @OA\JsonContent(
     *
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", nullable=true, example=null),
     *     @OA\Property(property="data", type="array", @OA\Items(type="string", example="photographer")),
     *     @OA\Property(property="errors", nullable=true, example=null)
     *   ))
     * )
     */
    public function categories(): JsonResponse
    {
        return $this->success(VendorCategory::values());
    }

    /**
     * @OA\Get(path="/api/v1/vendor/profile", tags={"Vendors"}, security={{"sanctum":{}}}, summary="Get own vendor profile",
     *   description="Vendor role required. The returned object has exactly the fields defined by VendorProfile.",
     *
     *   @OA\Response(response=200, description="Own vendor profile", @OA\JsonContent(
     *
     *     @OA\Property(property="success",type="boolean",example=true), @OA\Property(property="message",nullable=true,example=null),
     *     @OA\Property(property="data",ref="#/components/schemas/VendorProfile"), @OA\Property(property="errors",nullable=true,example=null)
     *   )),
     *
     *   @OA\Response(response=401,description="Missing or invalid Bearer token",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=403,description="Authenticated user is not Vendor",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *   @OA\Response(response=404,description="Vendor profile was not created",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(): JsonResponse
    {
        return $this->success(request()->user()->vendorProfile()->firstOrFail());
    }

    /**
     * @OA\Post(path="/api/v1/vendor/profile",tags={"Vendors"},security={{"sanctum":{}}},summary="Create own vendor profile",
     *   description="Send multipart/form-data. Required fields are name and at least one categories[] value. Arrays use PHP bracket names.",
     *
     *   @OA\RequestBody(required=true,@OA\MediaType(mediaType="multipart/form-data",@OA\Schema(required={"name","categories[]"},
     *
     *     @OA\Property(property="name",type="string",maxLength=255,example="Studio Foto Kowalski"), @OA\Property(property="description",type="string",example="Reportaże ślubne w całej Polsce."),
     *     @OA\Property(property="phone",type="string",maxLength=30,example="+48500100200"), @OA\Property(property="email",type="string",format="email",example="kontakt@studio.pl"),
     *     @OA\Property(property="website",type="string",format="uri",example="https://studio.pl"), @OA\Property(property="social_media[instagram]",type="string",format="uri",example="https://instagram.com/studio"),
     *     @OA\Property(property="voivodeship",type="string",maxLength=100,example="mazowieckie"), @OA\Property(property="cities[]",type="string",maxLength=100,example="Warszawa"),
     *     @OA\Property(property="categories[]",type="string",enum={"photographer","videographer","dj","band","venue","florist","decorations","cake","car","makeup","hairdresser","wedding_planner"},example="photographer"),
     *     @OA\Property(property="images[]",type="string",format="binary",description="Image; max 5 MB; maximum 10 files."), @OA\Property(property="price_list",type="string",format="binary",description="PDF, JPG, JPEG or PNG; max 10 MB.")
     *   ))),
     *
     *   @OA\Response(response=201,description="Created",@OA\JsonContent(@OA\Property(property="success",type="boolean",example=true),@OA\Property(property="message",type="string",example="Profil usługodawcy został utworzony."),@OA\Property(property="data",ref="#/components/schemas/VendorProfile"),@OA\Property(property="errors",nullable=true,example=null))),
     *   @OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=403,description="Wrong role",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=409,description="Profile exists",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")), @OA\Response(response=422,description="Validation error",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(VendorProfileRequest $request): JsonResponse
    {
        return $this->success($this->profiles->create($request->user(), $request->validated()), __('app.vendor.created'), 201);
    }

    /**
     * @OA\Patch(path="/api/v1/vendor/profile",tags={"Vendors"},security={{"sanctum":{}}},summary="Update own vendor profile",
     *   description="Same multipart fields, types, enum values and file limits as POST. Current validation requires name and categories[] on each PATCH.",
     *
     *   @OA\RequestBody(required=true,@OA\MediaType(mediaType="multipart/form-data",@OA\Schema(required={"name","categories[]"},@OA\Property(property="name",type="string",example="Studio Foto Kowalski"),@OA\Property(property="categories[]",type="string",example="photographer"),@OA\Property(property="images[]",type="string",format="binary"),@OA\Property(property="price_list",type="string",format="binary")))),
     *
     *   @OA\Response(response=200,description="Updated",@OA\JsonContent(@OA\Property(property="success",type="boolean",example=true),@OA\Property(property="message",type="string",example="Profil usługodawcy został zaktualizowany."),@OA\Property(property="data",ref="#/components/schemas/VendorProfile"),@OA\Property(property="errors",nullable=true,example=null))),
     *   @OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=403,description="Wrong role",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=404,description="Profile absent",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=422,description="Validation error",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(VendorProfileRequest $request): JsonResponse
    {
        return $this->success($this->profiles->update($request->user(), $request->validated()), __('app.vendor.updated'));
    }
}
