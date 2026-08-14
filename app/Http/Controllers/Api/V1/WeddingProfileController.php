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

    /** @OA\Get(path="/api/v1/profile", tags={"Wedding profile"}, security={{"sanctum":{}}}, summary="Get current user's wedding profile", @OA\Response(response=200, description="Profile returned"), @OA\Response(response=404, description="Profile not found")) */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(new WeddingProfileResource($user->weddingProfile()->firstOrFail()));
    }

    /** @OA\Post(path="/api/v1/profile", tags={"Wedding profile"}, security={{"sanctum":{}}}, summary="Create current user's wedding profile", @OA\RequestBody(required=true, @OA\JsonContent(required={"first_name", "last_name"}, @OA\Property(property="first_name", type="string"), @OA\Property(property="last_name", type="string"), @OA\Property(property="wedding_date", type="string", format="date"), @OA\Property(property="budget", type="number"), @OA\Property(property="guest_count", type="integer"), @OA\Property(property="location", type="string"))), @OA\Response(response=201, description="Profile created"), @OA\Response(response=422, description="Validation error")) */
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

    /** @OA\Patch(path="/api/v1/profile", tags={"Wedding profile"}, security={{"sanctum":{}}}, summary="Update current user's wedding profile", @OA\Response(response=200, description="Profile updated"), @OA\Response(response=404, description="Profile not found"), @OA\Response(response=422, description="Validation error")) */
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
