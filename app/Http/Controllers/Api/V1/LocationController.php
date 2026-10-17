<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Location\Contracts\LocationCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Location\CitySearchRequest;
use App\Http\Requests\Api\V1\Location\RegionListRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class LocationController extends Controller
{
    public function __construct(private readonly LocationCatalog $locations) {}

    /** @OA\Get(path="/api/v1/locations/countries",tags={"Locations"},summary="List supported countries",@OA\Response(response=200,description="Countries",@OA\JsonContent(@OA\Property(property="success",type="boolean"),@OA\Property(property="data",type="array",@OA\Items(ref="#/components/schemas/Country"))))) */
    public function countries(): JsonResponse
    {
        return $this->success($this->locations->countries());
    }

    /** @OA\Get(path="/api/v1/locations/regions",tags={"Locations"},summary="List regions for a country",@OA\Parameter(name="country",in="query",required=true,@OA\Schema(type="string",minLength=2,maxLength=2,example="PL")),@OA\Response(response=200,description="Regions",@OA\JsonContent(@OA\Property(property="success",type="boolean"),@OA\Property(property="data",type="array",@OA\Items(ref="#/components/schemas/Region")))),@OA\Response(response=422,description="Validation error",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))) */
    public function regions(RegionListRequest $request): JsonResponse
    {
        return $this->success($this->locations->regions($request->string('country')->toString()));
    }

    /** @OA\Get(path="/api/v1/locations/cities",tags={"Locations"},summary="Search real cities from the GeoNames catalog",description="Results come from the bundled GeoNames cities5000 extract and are ordered by population.",@OA\Parameter(name="country",in="query",required=true,@OA\Schema(type="string",example="PL")),@OA\Parameter(name="region",in="query",required=false,@OA\Schema(type="string",example="78")),@OA\Parameter(name="query",in="query",required=false,@OA\Schema(type="string",maxLength=100,example="Warsz")),@OA\Parameter(name="limit",in="query",required=false,@OA\Schema(type="integer",minimum=1,maximum=50,default=20)),@OA\Response(response=200,description="Matching cities",@OA\JsonContent(@OA\Property(property="success",type="boolean"),@OA\Property(property="data",type="array",@OA\Items(ref="#/components/schemas/City")))),@OA\Response(response=422,description="Validation error",@OA\JsonContent(ref="#/components/schemas/ErrorResponse"))) */
    public function cities(CitySearchRequest $request): JsonResponse
    {
        return $this->success($this->locations->searchCities(
            $request->string('country')->toString(),
            $request->filled('region') ? $request->string('region')->toString() : null,
            $request->string('query')->toString(),
            $request->integer('limit', 20),
        ));
    }
}
