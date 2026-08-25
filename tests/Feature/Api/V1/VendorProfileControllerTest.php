<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_and_update_own_profile(): void
    {
        $user = $this->vendor();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/vendor/profile', ['name' => 'Studio Foto', 'categories' => ['photographer'], 'cities' => ['Warszawa']])->assertCreated()->assertJsonPath('data.name', 'Studio Foto');
        $this->patchJson('/api/v1/vendor/profile', ['name' => 'Studio Foto Plus', 'categories' => ['photographer', 'videographer']])->assertOk()->assertJsonPath('data.name', 'Studio Foto Plus');
    }

    public function test_vendor_profile_access_is_restricted_and_validated(): void
    {
        $this->postJson('/api/v1/vendor/profile', [])->assertUnauthorized();
        $this->get('/api/v1/vendor/profile')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/vendor/profile')->assertForbidden();
        Sanctum::actingAs($this->vendor());
        $this->postJson('/api/v1/vendor/profile', ['name' => '', 'categories' => ['unknown']])->assertUnprocessable()->assertJsonValidationErrors(['name', 'categories.0']);
        $this->getJson('/api/v1/vendor/profile')->assertNotFound();
    }

    public function test_categories_are_public(): void
    {
        $this->getJson('/api/v1/vendor-categories')->assertOk()->assertJsonPath('success', true)->assertJsonFragment(['photographer']);
    }

    private function vendor(): User
    {
        Role::findOrCreate('vendor', 'web');
        $user = User::factory()->create();
        $user->assignRole('vendor');

        return $user;
    }
}
