<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Vendor\Models\VendorProfile;
use App\Domain\Vendor\Models\VendorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bride_can_idempotently_save_list_and_remove_a_vendor(): void
    {
        $bride = $this->userWithRole('bride');
        $vendor = $this->vendorProfile('Studio Foto');
        VendorReview::query()->forceCreate([
            'vendor_profile_id' => $vendor->id,
            'user_id' => $this->userWithRole('groom')->id,
            'rating' => 5,
        ]);
        Sanctum::actingAs($bride);

        $firstResponse = $this->putJson("/api/v1/favorites/vendors/{$vendor->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Vendor has been added to favorites.')
            ->assertJsonPath('data.vendor.id', $vendor->id)
            ->assertJsonPath('data.vendor.rating', 5)
            ->assertJsonPath('data.vendor.reviews_count', 1);

        $favoritedAt = $firstResponse->json('data.favorited_at');
        $this->putJson("/api/v1/favorites/vendors/{$vendor->id}")
            ->assertOk()
            ->assertJsonPath('data.favorited_at', $favoritedAt);
        $this->assertDatabaseCount('vendor_favorites', 1);

        $this->getJson('/api/v1/favorites/vendors?per_page=10')
            ->assertOk()
            ->assertJsonPath('data.items.0.vendor.id', $vendor->id)
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonCount(1, 'data.items');

        $this->deleteJson("/api/v1/favorites/vendors/{$vendor->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Vendor has been removed from favorites.');
        $this->deleteJson("/api/v1/favorites/vendors/{$vendor->id}")->assertOk();
        $this->assertDatabaseCount('vendor_favorites', 0);
    }

    public function test_favorites_are_private_to_each_planner_account(): void
    {
        $bride = $this->userWithRole('bride');
        $groom = $this->userWithRole('groom');
        $vendor = $this->vendorProfile('Wedding DJ');

        Sanctum::actingAs($bride);
        $this->putJson("/api/v1/favorites/vendors/{$vendor->id}")->assertOk();

        Sanctum::actingAs($groom);
        $this->getJson('/api/v1/favorites/vendors')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_favorites_require_authentication_and_planner_role(): void
    {
        $vendor = $this->vendorProfile('Wedding Hall');

        $this->getJson('/api/v1/favorites/vendors')->assertUnauthorized();
        $this->putJson("/api/v1/favorites/vendors/{$vendor->id}")->assertUnauthorized();

        Sanctum::actingAs($this->userWithRole('vendor'));
        $this->getJson('/api/v1/favorites/vendors')->assertForbidden();
        $this->putJson("/api/v1/favorites/vendors/{$vendor->id}")->assertForbidden();
    }

    public function test_favorites_validate_pagination_and_return_not_found_for_missing_vendor(): void
    {
        Sanctum::actingAs($this->userWithRole('bride'));

        $this->getJson('/api/v1/favorites/vendors?per_page=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
        $this->putJson('/api/v1/favorites/vendors/999999')->assertNotFound();
        $this->deleteJson('/api/v1/favorites/vendors/999999')->assertNotFound();
    }

    private function vendorProfile(string $name): VendorProfile
    {
        return User::factory()->create()->vendorProfile()->create([
            'name' => $name,
            'categories' => ['photographer'],
            'cities' => ['Warszawa'],
            'voivodeship' => 'mazowieckie',
            'price_from' => '2500.00',
            'price_to' => '5000.00',
        ]);
    }

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
