<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Vendor\Models\VendorProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketplace\SearchVendorsRequest;
use App\Http\Resources\Api\V1\MarketplaceVendorResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class MarketplaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/marketplace/vendors",
     *     tags={"Marketplace"},
     *     summary="Search vendor profiles",
     *     description="Public, paginated marketplace search. All supplied filters are combined with AND. price_min matches profiles whose maximum price is at least that value; price_max matches profiles whose minimum price is at most that value. date must fall inclusively between available_from and available_to. rating is the average of submitted reviews and is null when a vendor has no reviews.",
     *
     *     @OA\Parameter(name="category", in="query", description="Exact vendor category identifier.", @OA\Schema(type="string", enum={"photographer","videographer","dj","band","venue","florist","decorations","cake","car","makeup","hairdresser","wedding_planner"}, example="photographer")),
     *     @OA\Parameter(name="voivodeship", in="query", description="Exact voivodeship name; maximum 100 characters.", @OA\Schema(type="string", maxLength=100, example="mazowieckie")),
     *     @OA\Parameter(name="city", in="query", description="Exact city from vendor cities; maximum 100 characters.", @OA\Schema(type="string", maxLength=100, example="Warszawa")),
     *     @OA\Parameter(name="price_min", in="query", description="Minimum desired price in PLN; zero or greater.", @OA\Schema(type="number", format="float", minimum=0, example=3000)),
     *     @OA\Parameter(name="price_max", in="query", description="Maximum desired price in PLN; must not be lower than price_min.", @OA\Schema(type="number", format="float", minimum=0, example=8000)),
     *     @OA\Parameter(name="date", in="query", description="Required availability date in ISO 8601 date format.", @OA\Schema(type="string", format="date", example="2027-06-12")),
     *     @OA\Parameter(name="min_rating", in="query", description="Minimum average rating, inclusive; decimal from 1 through 5. Vendors without reviews do not match.", @OA\Schema(type="number", format="float", minimum=1, maximum=5, example=4.5)),
     *     @OA\Parameter(name="per_page", in="query", description="Number of results per page; default 15, maximum 50.", @OA\Schema(type="integer", minimum=1, maximum=50, default=15, example=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated vendor results. data is a Laravel paginator: its numeric indexes contain marketplace vendor items and it also exposes pagination metadata.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", nullable=true, example=null),
     *             @OA\Property(property="data", type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MarketplaceVendor")), @OA\Property(property="current_page", type="integer", example=1), @OA\Property(property="per_page", type="integer", example=15), @OA\Property(property="total", type="integer", example=1)),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="One or more query parameters are invalid.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(SearchVendorsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $vendors = VendorProfile::query()->withAvg('reviews', 'rating')->withCount('reviews');

        if (isset($filters['category'])) {
            $vendors->where('categories', 'like', '%"'.$filters['category'].'"%');
        }

        if (isset($filters['voivodeship'])) {
            $vendors->where('voivodeship', $filters['voivodeship']);
        }

        if (isset($filters['city'])) {
            $vendors->where('cities', 'like', '%"'.$filters['city'].'"%');
        }

        if (isset($filters['price_min'])) {
            $vendors->where('price_to', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $vendors->where('price_from', '<=', $filters['price_max']);
        }

        if (isset($filters['date'])) {
            $vendors->whereDate('available_from', '<=', $filters['date'])
                ->whereDate('available_to', '>=', $filters['date']);
        }

        if (isset($filters['min_rating'])) {
            $vendors->whereRaw(
                '(select avg(rating) from vendor_reviews where vendor_reviews.vendor_profile_id = vendor_profiles.id) >= cast(? as decimal(3,2))',
                [$filters['min_rating']],
            );
        }

        return $this->success(MarketplaceVendorResource::collection($vendors->paginate($filters['per_page'] ?? 15)->appends($filters)));
    }
}
