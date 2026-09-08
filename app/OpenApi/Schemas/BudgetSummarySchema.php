<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="BudgetSummary",
 *     required={"total_budget","estimated_total","spent_total","remaining","planned_remaining","currency","items"},
 *     description="Complete budget dashboard for the authenticated account. All monetary values are exact decimal strings.",
 *
 *     @OA\Property(property="total_budget", type="string", example="60000.00", description="Budget limit configured in the wedding profile."),
 *     @OA\Property(property="estimated_total", type="string", example="52000.00", description="Sum of estimated_amount for all items."),
 *     @OA\Property(property="spent_total", type="string", example="42000.00", description="Sum of paid_amount for all items."),
 *     @OA\Property(property="remaining", type="string", example="18000.00", description="total_budget - spent_total; may be negative when the budget is exceeded."),
 *     @OA\Property(property="planned_remaining", type="string", example="8000.00", description="total_budget - estimated_total; may be negative when planned costs exceed the budget."),
 *     @OA\Property(property="currency", type="string", enum={"PLN"}, example="PLN"),
 *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/BudgetItem"))
 * )
 */
final class BudgetSummarySchema {}
