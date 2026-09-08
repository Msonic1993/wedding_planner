<?php

namespace App\Application\Budget\Contracts;

use App\Domain\Budget\Models\BudgetItem;
use App\Models\User;

interface BudgetService
{
    /** @return array{total_budget: string, estimated_total: string, spent_total: string, remaining: string, planned_remaining: string, currency: string, items: iterable<BudgetItem>} */
    public function summary(User $user): array;

    /** @param array<string, mixed> $attributes */
    public function createItem(User $user, array $attributes): BudgetItem;

    public function getItem(User $user, int $itemId): BudgetItem;

    /** @param array<string, mixed> $attributes */
    public function updateItem(User $user, int $itemId, array $attributes): BudgetItem;

    public function deleteItem(User $user, int $itemId): void;
}
