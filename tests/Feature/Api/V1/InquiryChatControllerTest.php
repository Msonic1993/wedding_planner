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
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages?per_page=1")
            ->assertOk()
            ->assertJsonPath('data.items.0.body', 'Yes, the date is available.')
            ->assertJsonPath('data.pagination.last_page', 2);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.items.0.body', 'Are you available on 12 June 2027?')
            ->assertJsonPath('data.items.1.body', 'Yes, the date is available.')
            ->assertJsonPath('data.pagination.total', 2);
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
            ->assertJsonPath('data.items.0.id', $inquiry->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.pagination.total', 1);
        $this->getJson("/api/v1/chat/inquiries/{$otherInquiry->id}/messages")->assertForbidden();

        Sanctum::actingAs($vendorUser);
        $this->getJson('/api/v1/chat/inquiries')->assertOk()->assertJsonCount(2, 'data.items');
    }

    public function test_participants_can_open_thread_context_but_other_users_cannot(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $otherBride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);

        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}")->assertUnauthorized();

        Sanctum::actingAs($bride);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $inquiry->id)
            ->assertJsonPath('data.vendor.id', $vendorProfile->id);

        Sanctum::actingAs($vendorUser);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}")
            ->assertOk()
            ->assertJsonPath('data.couple.id', $bride->id);

        Sanctum::actingAs($otherBride);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}")->assertForbidden();
        $this->getJson('/api/v1/chat/inquiries/999999')->assertNotFound();
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
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", [
            'body' => 'Is this date available?',
            'requested_date' => '2020-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['requested_date']);
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello'])->assertCreated();
        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello again'])
            ->assertConflict()
            ->assertJsonPath('message', 'You already have an inquiry with this vendor.');

        $this->getJson('/api/v1/chat/inquiries?page=0')->assertUnprocessable()->assertJsonValidationErrors(['page']);
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
