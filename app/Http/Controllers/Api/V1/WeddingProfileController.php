<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Profile\Contracts\WeddingProfileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\StoreWeddingProfileRequest;
use App\Http\Requests\Api\V1\Profile\UpdateWeddingProfileRequest;
use App\Http\Resources\Api\V1\WeddingProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class WeddingProfileController extends Controller
{
    public function __construct(private readonly WeddingProfileService $profiles) {}

    /** @OA\Get(path="/api/v1/profile", tags={"Wedding profile"}, security={{"sanctum":{}}}, summary="Get current user's wedding profile", description="Only Bride or Groom. Returns the individual planning profile.", @OA\Response(response=200,description="Profile",@OA\JsonContent(required={"success","message","data","errors"},@OA\Property(property="success",type="boolean",example=true),@OA\Property(property="message",nullable=true),@OA\Property(property="data",ref="#/components/schemas/WeddingProfile"),@OA\Property(property="errors",nullable=true))),@OA\Response(response=401,description="Unauthenticated",ref="#/components/schemas/ErrorResponse"),@OA\Response(response=403,description="Wrong role",ref="#/components/schemas/ErrorResponse"),@OA\Response(response=404,description="Profile missing",ref="#/components/schemas/ErrorResponse")) */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(new WeddingProfileResource($user->weddingProfile()->firstOrFail()));
    }

    /** @OA\Post(path="/api/v1/profile",tags={"Wedding profile"},security={{"sanctum":{}}},summary="Create wedding profile",description="multipart/form-data when photo is included. Photo: JPEG/PNG/WebP image up to 5 MB.",@OA\RequestBody(required=true,@OA\MediaType(mediaType="multipart/form-data",@OA\Schema(required={"first_name","last_name"},@OA\Property(property="first_name",type="string",maxLength=100),@OA\Property(property="last_name",type="string",maxLength=100),@OA\Property(property="wedding_date",type="string",format="date",nullable=true),@OA\Property(property="budget",type="number",format="float",minimum=0,nullable=true),@OA\Property(property="guest_count",type="integer",minimum=1,nullable=true),@OA\Property(property="location",type="string",maxLength=255,nullable=true),@OA\Property(property="photo",type="string",format="binary",nullable=true)))),@OA\Response(response=201,description="Created"),@OA\Response(response=409,description="Already exists",ref="#/components/schemas/ErrorResponse"),@OA\Response(response=422,description="Validation error",ref="#/components/schemas/ErrorResponse")) */
    public function store(StoreWeddingProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            new WeddingProfileResource($this->profiles->create($user, $request->validated())),
            __('app.profile.created'),
            Response::HTTP_CREATED,
        );
    }

    /**
     * @OA\Patch(path="/api/v1/profile",tags={"Wedding profile"},security={{"sanctum":{}}},summary="Partially update wedding profile",description="multipart/form-data. Every field is optional; send only fields to change. photo must be an image up to 5 MB.",@OA\RequestBody(required=true,@OA\MediaType(mediaType="multipart/form-data",@OA\Schema(@OA\Property(property="first_name",type="string",maxLength=100,example="Anna"),@OA\Property(property="last_name",type="string",maxLength=100,example="Kowalska"),@OA\Property(property="wedding_date",type="string",format="date",example="2027-06-12"),@OA\Property(property="budget",type="number",minimum=0,maximum=9999999999.99,example=60000),@OA\Property(property="guest_count",type="integer",minimum=1,maximum=10000,example=80),@OA\Property(property="location",type="string",maxLength=255,example="Warszawa"),@OA\Property(property="photo",type="string",format="binary")))),@OA\Response(response=200,description="Updated; data is WeddingProfile"),@OA\Response(response=401,description="Unauthenticated",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=403,description="Bride or Groom role required",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=404,description="Profile absent",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")),@OA\Response(response=422,description="Validation error",@OA\JsonContent(ref="#/components/schemas/ErrorResponse")))
     */
    public function update(UpdateWeddingProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            new WeddingProfileResource($this->profiles->update($user, $request->validated())),
            __('app.profile.updated'),
        );
    }
}
