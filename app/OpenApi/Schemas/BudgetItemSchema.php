<?php

namespace App\OpenApi\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="BudgetItem",
 *     required={"id","name","category","estimated_amount","paid_amount","outstanding_amount","payment_status","due_date","notes","created_at","updated_at"},
 *     description="A wedding expense owned by the authenticated Bride or Groom account. Monetary fields are decimal strings in PLN to preserve exact values in JavaScript and React Native.",
 *
 *     @OA\Property(property="id", type="integer", format="int64", example=18, description="Stable budget item identifier."),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Wedding venue deposit", description="User-facing expense name."),
 *     @OA\Property(property="category", type="string", enum={"venue","catering","photographer","videographer","dj","band","decorations","florist","cake","attire","rings","transport","alcohol","invitations","makeup","hairdresser","wedding_planner","other"}, example="venue"),
 *     @OA\Property(property="estimated_amount", type="string", pattern="^-?[0-9]+\.[0-9]{2}$", example="25000.00", description="Planned cost in PLN."),
 *     @OA\Property(property="paid_amount", type="string", pattern="^-?[0-9]+\.[0-9]{2}$", example="5000.00", description="Amount already paid in PLN."),
 *     @OA\Property(property="outstanding_amount", type="string", pattern="^[0-9]+\.[0-9]{2}$", example="20000.00", description="max(estimated_amount - paid_amount, 0), calculated by the API."),
 *     @OA\Property(property="payment_status", type="string", enum={"unpaid","partially_paid","paid"}, example="partially_paid", description="Calculated payment state; paid also covers overpayment."),
 *     @OA\Property(property="due_date", type="string", format="date", nullable=true, example="2027-01-15"),
 *     @OA\Property(property="notes", type="string", nullable=true, maxLength=5000, example="Second instalment due after signing the contract."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-09-08T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-09-08T12:15:00.000000Z")
 * )
 */
final class BudgetItemSchema {}
