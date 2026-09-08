<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Budget\Contracts\BudgetService;
use App\Domain\Budget\Enums\BudgetCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Budget\StoreBudgetItemRequest;
use App\Http\Requests\Api\V1\Budget\UpdateBudgetItemRequest;
use App\Http\Resources\Api\V1\BudgetItemResource;
use App\Http\Resources\Api\V1\BudgetSummaryResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Annotations as OA;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgets) {}

    /**
     * @OA\Get(
     *     path="/api/v1/budget/categories",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="List supported budget categories",
     *     description="Returns stable identifiers accepted by category in POST and PATCH. Mobile clients should use these identifiers as values and translate their labels locally. Only Bride and Groom roles may use this endpoint.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Category identifiers.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", nullable=true, example=null),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string", example="venue"), example={"venue","catering","photographer","dj","decorations","cake","attire","rings","transport","alcohol","invitations","other"}),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function categories(): JsonResponse
    {
        return $this->success(BudgetCategory::values());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/budget",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="Get budget dashboard",
     *     description="Returns the budget limit, aggregate values and every expense owned by the authenticated Bride or Groom. total_budget comes from PATCH /api/v1/profile field budget. Items are ordered by due_date ascending, with undated items last. Amounts are decimal strings with exactly two fraction digits; clients must not assume JSON number types.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Complete budget dashboard.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", nullable=true, example=null),
     *             @OA\Property(property="data", ref="#/components/schemas/BudgetSummary"),
     *             @OA\Property(property="errors", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Wedding profile does not exist; create it before using the budget.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(new BudgetSummaryResource($this->budgets->summary($user)));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/budget/items",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="Create a budget item",
     *     description="Creates an expense for the authenticated Bride or Groom. Monetary request fields are JSON numbers or decimal strings with at most two fraction digits. paid_amount defaults to 0.00. Values are stored as DECIMAL(12,2).",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Budget item payload in application/json.",
     *
     *         @OA\JsonContent(
     *             required={"name","category","estimated_amount"},
     *
     *             @OA\Property(property="name", type="string", maxLength=255, example="Wedding venue"),
     *             @OA\Property(property="category", type="string", enum={"venue","catering","photographer","videographer","dj","band","decorations","florist","cake","attire","rings","transport","alcohol","invitations","makeup","hairdresser","wedding_planner","other"}, example="venue"),
     *             @OA\Property(property="estimated_amount", oneOf={@OA\Schema(type="number", format="decimal", example=25000.00), @OA\Schema(type="string", example="25000.00")}, description="Required planned amount from 0.00 through 9999999999.99."),
     *             @OA\Property(property="paid_amount", oneOf={@OA\Schema(type="number", format="decimal", example=5000.00), @OA\Schema(type="string", example="5000.00")}, description="Optional amount already paid; defaults to 0.00."),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true, example="2027-01-15"),
     *             @OA\Property(property="notes", type="string", nullable=true, maxLength=5000, example="Second instalment after signing the contract.")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Budget item created.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Budget item has been created."), @OA\Property(property="data", ref="#/components/schemas/BudgetItem"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Wedding profile does not exist.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation failed. errors maps field names to translated message arrays.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreBudgetItemRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            new BudgetItemResource($this->budgets->createItem($user, $request->validated())),
            __('app.budget.item_created'),
            Response::HTTP_CREATED,
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/budget/items/{item}",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="Get one budget item",
     *     description="Returns an expense only when it belongs to the authenticated Bride or Groom. An item owned by another account is intentionally returned as 404 to avoid exposing private budget data.",
     *
     *     @OA\Parameter(name="item", in="path", required=true, description="Budget item identifier.", @OA\Schema(type="integer", format="int64", example=18)),
     *
     *     @OA\Response(response=200, description="Budget item.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", nullable=true, example=null), @OA\Property(property="data", ref="#/components/schemas/BudgetItem"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Item does not exist or belongs to another account.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function item(int $item): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        return $this->success(new BudgetItemResource($this->budgets->getItem($user, $item)));
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/budget/items/{item}",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="Partially update a budget item",
     *     description="Send only fields that should change. Field types, limits and category values are identical to POST /api/v1/budget/items. Set due_date or notes to null to clear it. Omitted fields retain their current values.",
     *
     *     @OA\Parameter(name="item", in="path", required=true, description="Budget item identifier.", @OA\Schema(type="integer", format="int64", example=18)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="name", type="string", maxLength=255, example="Wedding venue and accommodation"), @OA\Property(property="category", type="string", example="venue"), @OA\Property(property="estimated_amount", oneOf={@OA\Schema(type="number", example=27000.00), @OA\Schema(type="string", example="27000.00")}), @OA\Property(property="paid_amount", oneOf={@OA\Schema(type="number", example=10000.00), @OA\Schema(type="string", example="10000.00")}), @OA\Property(property="due_date", type="string", format="date", nullable=true, example="2027-02-01"), @OA\Property(property="notes", type="string", nullable=true, maxLength=5000, example=null))),
     *
     *     @OA\Response(response=200, description="Budget item updated.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Budget item has been updated."), @OA\Property(property="data", ref="#/components/schemas/BudgetItem"), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Item does not exist or belongs to another account.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="One or more supplied fields are invalid.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function update(UpdateBudgetItemRequest $request, int $item): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            new BudgetItemResource($this->budgets->updateItem($user, $item, $request->validated())),
            __('app.budget.item_updated'),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/budget/items/{item}",
     *     tags={"Budget"},
     *     security={{"sanctum":{}}},
     *     summary="Delete a budget item",
     *     description="Permanently removes one expense owned by the authenticated account. The next GET /api/v1/budget recalculates all totals without this item.",
     *
     *     @OA\Parameter(name="item", in="path", required=true, description="Budget item identifier.", @OA\Schema(type="integer", format="int64", example=18)),
     *
     *     @OA\Response(response=200, description="Budget item deleted.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Budget item has been deleted."), @OA\Property(property="data", nullable=true, example=null), @OA\Property(property="errors", nullable=true, example=null))),
     *     @OA\Response(response=401, description="Missing or invalid Bearer token.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Authenticated account is not Bride or Groom.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Item does not exist or belongs to another account.", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function destroy(int $item): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();
        $this->budgets->deleteItem($user, $item);

        return $this->success(message: __('app.budget.item_deleted'));
    }
}
