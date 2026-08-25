<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Vendor\Models\VendorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_filters_vendor_profiles(): void
    {
        $match = User::factory()->create()->vendorProfile()->create(['name' => 'Foto Warszawa', 'categories' => ['photographer'], 'cities' => ['Warszawa'], 'voivodeship' => 'mazowieckie', 'price_from' => 2000, 'price_to' => 5000, 'available_from' => '2027-01-01', 'available_to' => '2027-12-31']);
        User::factory()->create()->vendorProfile()->create(['name' => 'DJ Krakow', 'categories' => ['dj'], 'cities' => ['Krakow'], 'voivodeship' => 'malopolskie', 'price_from' => 1000, 'price_to' => 2000]);

        $this->getJson('/api/v1/marketplace/vendors?category=photographer&voivodeship=mazowieckie&city=Warszawa&price_min=3000&price_max=6000&date=2027-06-12')->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_validates_filters(): void
    {
        $this->getJson('/api/v1/marketplace/vendors?category=wrong&per_page=100')->assertUnprocessable()->assertJsonPath('success', false)->assertJsonValidationErrors(['category', 'per_page']);
    }

    public function test_search_returns_rating_and_filters_by_minimum_average_rating(): void
    {
        $highRated = User::factory()->create()->vendorProfile()->create(['name' => 'Five Stars', 'categories' => ['photographer']]);
        $lowRated = User::factory()->create()->vendorProfile()->create(['name' => 'Two Stars', 'categories' => ['photographer']]);
        $reviewer = User::factory()->create();
        VendorReview::query()->forceCreate(['vendor_profile_id' => $highRated->id, 'user_id' => $reviewer->id, 'rating' => 5]);
        VendorReview::query()->forceCreate(['vendor_profile_id' => $lowRated->id, 'user_id' => User::factory()->create()->id, 'rating' => 2]);
        $response = $this->getJson('/api/v1/marketplace/vendors?min_rating=4');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $highRated->id)
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.0.reviews_count', 1)
            ->assertJsonCount(1, 'data');
    }
}
