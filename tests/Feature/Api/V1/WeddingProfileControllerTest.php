<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Profile\Models\WeddingProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WeddingProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bride_can_create_profile_with_a_photo_and_read_it(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('bride');
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/profile', [
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'wedding_date' => now()->addYear()->toDateString(),
            'budget' => 60000,
            'guest_count' => 80,
            'location' => 'Warszawa',
            'photo' => UploadedFile::fake()->image('profile.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Anna')
            ->assertJsonPath('data.location', 'Warszawa');

        $profile = WeddingProfile::query()->where('user_id', $user->id)->firstOrFail();
        Storage::disk('public')->assertExists($profile->photo_path);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id)
            ->assertJsonPath('data.photo_url', Storage::disk('public')->url($profile->photo_path));
    }

    public function test_groom_can_update_own_profile(): void
    {
        $user = $this->userWithRole('groom');
        $user->weddingProfile()->create([
            'first_name' => 'Jan',
            'last_name' => 'Nowak',
            'location' => 'Gdańsk',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/profile', ['guest_count' => 120, 'location' => 'Sopot'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.guest_count', 120)
            ->assertJsonPath('data.location', 'Sopot');
    }

    public function test_vendor_cannot_access_wedding_profiles_and_guest_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized()->assertJsonPath('success', false);

        Sanctum::actingAs($this->userWithRole('vendor'));
        $this->getJson('/api/v1/profile')->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_profile_creation_validates_input_and_existing_profile_cannot_be_created_again(): void
    {
        $user = $this->userWithRole('bride');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'first_name' => '',
            'last_name' => '',
            'wedding_date' => now()->subDay()->toDateString(),
            'budget' => -1,
            'guest_count' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'wedding_date',
            'budget',
            'guest_count',
        ]);

        $user->weddingProfile()->create([
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
        ]);

        $this->postJson('/api/v1/profile', ['first_name' => 'Anna', 'last_name' => 'Kowalska'])
            ->assertConflict()
            ->assertJsonPath('success', false);
    }

    public function test_missing_profile_returns_not_found(): void
    {
        Sanctum::actingAs($this->userWithRole('bride'));

        $this->getJson('/api/v1/profile')->assertNotFound()->assertJsonPath('success', false);
        $this->patchJson('/api/v1/profile', ['location' => 'Kraków'])->assertNotFound()->assertJsonPath('success', false);
    }

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
