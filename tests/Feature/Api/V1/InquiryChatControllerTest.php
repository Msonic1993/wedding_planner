<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InquiryChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bride_can_create_an_inquiry_and_vendor_can_reply(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        Sanctum::actingAs($bride);

        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", [
            'body' => 'Are you available on 12 June 2027?',
            'requested_date' => '2027-06-12',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Inquiry has been created.')
            ->assertJsonPath('data.vendor.id', $vendorProfile->id)
            ->assertJsonPath('data.couple.id', $bride->id)
            ->assertJsonPath('data.requested_date', '2027-06-12');

        $inquiry = VendorInquiry::query()->firstOrFail();
        $this->assertDatabaseHas('chat_messages', [
            'vendor_inquiry_id' => $inquiry->id,
            'sender_id' => $bride->id,
            'body' => 'Are you available on 12 June 2027?',
        ]);

        Sanctum::actingAs($vendorUser);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Yes, the date is available.'])
            ->assertCreated()
            ->assertJsonPath('message', 'Message has been sent.')
            ->assertJsonPath('data.sender.id', $vendorUser->id);

        Sanctum::actingAs($bride);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Are you available on 12 June 2027?')
            ->assertJsonPath('data.1.body', 'Yes, the date is available.');
    }

    public function test_chat_lists_only_current_users_threads_and_denies_other_users(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $otherBride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);
        $otherInquiry = $this->createInquiry($otherBride, $vendorProfile);

        Sanctum::actingAs($bride);
        $this->getJson('/api/v1/chat/inquiries')->assertOk()
            ->assertJsonPath('data.0.id', $inquiry->id)
            ->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/chat/inquiries/{$otherInquiry->id}/messages")->assertForbidden();

        Sanctum::actingAs($vendorUser);
        $this->getJson('/api/v1/chat/inquiries')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_inquiry_requires_bride_or_groom_and_valid_body_and_is_unique(): void
    {
        [, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');

        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello'])->assertForbidden();

        Sanctum::actingAs($bride);
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello'])->assertCreated();
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello again'])
            ->assertConflict()
            ->assertJsonPath('message', 'You already have an inquiry with this vendor.');
    }

    /** @return array{User, VendorProfile} */
    private function vendor(): array
    {
        $vendor = $this->userWithRole('vendor');
        $profile = $vendor->vendorProfile()->create(['name' => 'Studio Foto', 'categories' => ['photographer']]);

        return [$vendor, $profile];
    }

    private function createInquiry(User $couple, VendorProfile $vendor): VendorInquiry
    {
        $inquiry = new VendorInquiry;
        $inquiry->vendorProfile()->associate($vendor);
        $inquiry->couple()->associate($couple);
        $inquiry->save();

        return $inquiry;
    }

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
