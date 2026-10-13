<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Budget\Models\BudgetItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BudgetItem */
class BudgetItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->value,
            'estimated_amount' => $this->estimated_amount,
            'paid_amount' => $this->paid_amount,
            'outstanding_amount' => $this->outstandingAmount(),
            'payment_status' => $this->paymentStatus(),
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
