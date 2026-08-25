<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bride_can_create_update_and_delete_own_review(): void
    {
        $vendor = $this->vendorProfile();
        $bride = $this->bride();
        Sanctum::actingAs($bride);

        $this->postJson("/api/v1/vendors/{$vendor->id}/review", [
            'rating' => 5,
            'comment' => 'Excellent service.',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Review has been created.')
            ->assertJsonPath('data.rating', 5);

        $this->patchJson("/api/v1/vendors/{$vendor->id}/review", [
            'rating' => 4,
            'comment' => null,
        ])->assertOk()
            ->assertJsonPath('message', 'Review has been updated.')
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', null);

        $this->deleteJson("/api/v1/vendors/{$vendor->id}/review")
            ->assertOk()
            ->assertJsonPath('message', 'Review has been deleted.');

        $this->assertDatabaseMissing('vendor_reviews', [
            'vendor_profile_id' => $vendor->id,
            'user_id' => $bride->id,
        ]);
    }

    public function test_review_requires_authentication_correct_role_and_valid_data(): void
    {
        $vendor = $this->vendorProfile();

        $this->postJson("/api/v1/vendors/{$vendor->id}/review", ['rating' => 5])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/vendors/{$vendor->id}/review", ['rating' => 5])->assertForbidden();

        Sanctum::actingAs($this->bride());
        $this->postJson("/api/v1/vendors/{$vendor->id}/review", ['rating' => 6, 'comment' => str_repeat('x', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating', 'comment']);
    }

    public function test_user_cannot_create_a_second_review_for_the_same_vendor(): void
    {
        $vendor = $this->vendorProfile();
        $bride = $this->bride();
        Sanctum::actingAs($bride);

        $this->postJson("/api/v1/vendors/{$vendor->id}/review", ['rating' => 5])->assertCreated();
        $this->postJson("/api/v1/vendors/{$vendor->id}/review", ['rating' => 3])
            ->assertConflict()
            ->assertJsonPath('message', 'Your review for this vendor already exists.');
    }

    private function bride(): User
    {
        Role::findOrCreate('bride', 'web');
        $bride = User::factory()->create();
        $bride->assignRole('bride');

        return $bride;
    }

    private function vendorProfile(): VendorProfile
    {
        return User::factory()->create()->vendorProfile()->create([
            'name' => 'Studio Foto',
            'categories' => ['photographer'],
        ]);
    }
}
