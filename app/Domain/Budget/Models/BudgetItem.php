<?php

namespace App\Domain\Budget\Models;

use App\Domain\Budget\Enums\BudgetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property BudgetCategory $category
 * @property string $estimated_amount
 * @property string $paid_amount
 * @property Carbon|null $due_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'category', 'estimated_amount', 'paid_amount', 'due_date', 'notes'])]
class BudgetItem extends Model
{
    public function outstandingAmount(): string
    {
        $difference = bcsub($this->estimated_amount, $this->paid_amount, 2);

        return bccomp($difference, '0.00', 2) === 1 ? $difference : '0.00';
    }

    public function paymentStatus(): string
    {
        if (bccomp($this->paid_amount, '0.00', 2) === 0) {
            return 'unpaid';
        }

        return bccomp($this->paid_amount, $this->estimated_amount, 2) >= 0 ? 'paid' : 'partially_paid';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'category' => BudgetCategory::class,
            'estimated_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }
}
