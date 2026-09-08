<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_budget' => $this->resource['total_budget'],
            'estimated_total' => $this->resource['estimated_total'],
            'spent_total' => $this->resource['spent_total'],
            'remaining' => $this->resource['remaining'],
            'planned_remaining' => $this->resource['planned_remaining'],
            'currency' => $this->resource['currency'],
            'items' => BudgetItemResource::collection($this->resource['items']),
        ];
    }
}
