<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Budget\Models\BudgetItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bride_can_manage_items_and_receive_exact_budget_summary(): void
    {
        $bride = $this->planner('bride', '60000.00');
        Sanctum::actingAs($bride);

        $venueId = $this->postJson('/api/v1/budget/items', [
            'name' => 'Wedding venue',
            'category' => 'venue',
            'estimated_amount' => '30000.00',
            'paid_amount' => '10000.00',
            'due_date' => '2027-01-15',
            'notes' => 'Deposit paid.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Budget item has been created.')
            ->assertJsonPath('data.estimated_amount', '30000.00')
            ->assertJsonPath('data.outstanding_amount', '20000.00')
            ->assertJsonPath('data.payment_status', 'partially_paid')
            ->json('data.id');

        $this->postJson('/api/v1/budget/items', [
            'name' => 'Photographer',
            'category' => 'photographer',
            'estimated_amount' => '12000.00',
            'paid_amount' => '12000.00',
        ])->assertCreated()->assertJsonPath('data.payment_status', 'paid');

        $this->getJson('/api/v1/budget')
            ->assertOk()
            ->assertJsonPath('data.total_budget', '60000.00')
            ->assertJsonPath('data.estimated_total', '42000.00')
            ->assertJsonPath('data.spent_total', '22000.00')
            ->assertJsonPath('data.remaining', '38000.00')
            ->assertJsonPath('data.planned_remaining', '18000.00')
            ->assertJsonPath('data.currency', 'PLN')
            ->assertJsonCount(2, 'data.items');

        $this->getJson("/api/v1/budget/items/{$venueId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Wedding venue');

        $this->patchJson("/api/v1/budget/items/{$venueId}", ['paid_amount' => '30000.00'])
            ->assertOk()
            ->assertJsonPath('message', 'Budget item has been updated.')
            ->assertJsonPath('data.outstanding_amount', '0.00')
            ->assertJsonPath('data.payment_status', 'paid');

        $this->deleteJson("/api/v1/budget/items/{$venueId}")
            ->assertOk()
            ->assertJsonPath('message', 'Budget item has been deleted.');
        $this->assertDatabaseMissing('budget_items', ['id' => $venueId]);
    }

    public function test_budget_endpoints_require_authentication_role_and_wedding_profile(): void
    {
        $this->getJson('/api/v1/budget')->assertUnauthorized();

        Sanctum::actingAs($this->userWithRole('vendor'));
        $this->getJson('/api/v1/budget')->assertForbidden();

        Sanctum::actingAs($this->userWithRole('groom'));
        $this->getJson('/api/v1/budget')->assertNotFound();
        $this->postJson('/api/v1/budget/items', [
            'name' => 'DJ',
            'category' => 'dj',
            'estimated_amount' => '5000.00',
        ])->assertNotFound();
    }

    public function test_budget_item_input_is_validated(): void
    {
        Sanctum::actingAs($this->planner('bride', '50000.00'));

        $this->postJson('/api/v1/budget/items', [
            'name' => '',
            'category' => 'unsupported',
            'estimated_amount' => '-1.123',
            'paid_amount' => 'invalid',
            'due_date' => 'not-a-date',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'category', 'estimated_amount', 'paid_amount', 'due_date']);
    }

    public function test_user_cannot_read_update_or_delete_another_users_item(): void
    {
        $owner = $this->planner('bride', '50000.00');
        $other = $this->planner('groom', '40000.00');
        $item = new BudgetItem(['name' => 'Private expense', 'category' => 'other', 'estimated_amount' => '100.00']);
        $item->user()->associate($owner);
        $item->save();
        Sanctum::actingAs($other);

        $this->getJson("/api/v1/budget/items/{$item->id}")->assertNotFound();
        $this->patchJson("/api/v1/budget/items/{$item->id}", ['name' => 'Changed'])->assertNotFound();
        $this->deleteJson("/api/v1/budget/items/{$item->id}")->assertNotFound();
        $this->assertDatabaseHas('budget_items', ['id' => $item->id, 'name' => 'Private expense']);
    }

    public function test_budget_categories_are_available_to_planners(): void
    {
        Sanctum::actingAs($this->userWithRole('bride'));

        $this->getJson('/api/v1/budget/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['venue'])
            ->assertJsonFragment(['photographer'])
            ->assertJsonFragment(['other']);
    }

    private function planner(string $role, string $budget): User
    {
        $user = $this->userWithRole($role);
        $user->weddingProfile()->create([
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'budget' => $budget,
        ]);

        return $user;
    }

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
