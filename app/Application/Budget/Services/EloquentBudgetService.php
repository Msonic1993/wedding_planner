<?php

namespace App\Application\Budget\Services;

use App\Application\Budget\Contracts\BudgetService;
use App\Domain\Budget\Models\BudgetItem;
use App\Models\User;

class EloquentBudgetService implements BudgetService
{
    public function summary(User $user): array
    {
        $profile = $user->weddingProfile()->firstOrFail();
        $items = BudgetItem::query()->where('user_id', $user->id)->orderByRaw('due_date is null, due_date')->orderBy('id')->get();
        $estimatedTotal = $this->sum($items->pluck('estimated_amount')->all());
        $spentTotal = $this->sum($items->pluck('paid_amount')->all());
        $totalBudget = $this->money($profile->budget ?? '0');

        return [
            'total_budget' => $totalBudget,
            'estimated_total' => $estimatedTotal,
            'spent_total' => $spentTotal,
            'remaining' => bcsub($totalBudget, $spentTotal, 2),
            'planned_remaining' => bcsub($totalBudget, $estimatedTotal, 2),
            'currency' => 'PLN',
            'items' => $items,
        ];
    }

    public function createItem(User $user, array $attributes): BudgetItem
    {
        $user->weddingProfile()->firstOrFail();
        $item = new BudgetItem($attributes);
        $item->user()->associate($user);
        $item->save();

        return $item->refresh();
    }

    public function getItem(User $user, int $itemId): BudgetItem
    {
        return BudgetItem::query()->where('user_id', $user->id)->findOrFail($itemId);
    }

    public function updateItem(User $user, int $itemId, array $attributes): BudgetItem
    {
        $item = $this->getItem($user, $itemId);
        $item->update($attributes);

        return $item->refresh();
    }

    public function deleteItem(User $user, int $itemId): void
    {
        $this->getItem($user, $itemId)->delete();
    }

    /** @param array<int, mixed> $values */
    private function sum(array $values): string
    {
        return array_reduce($values, fn (string $sum, mixed $value): string => bcadd($sum, $this->money($value), 2), '0.00');
    }

    private function money(mixed $value): string
    {
        return bcadd((string) $value, '0', 2);
    }
}
