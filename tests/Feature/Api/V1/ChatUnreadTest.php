<?php

namespace Tests\Feature\Api\V1;

use App\Application\Chat\Events\ChatUnreadUpdated;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesChatParticipants;
use Tests\TestCase;

class ChatUnreadTest extends TestCase
{
    use CreatesChatParticipants;
    use RefreshDatabase;

    public function test_new_message_is_unread_for_recipient_and_read_for_sender(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->startThread($bride, $vendorProfile, 'Hello');

        Sanctum::actingAs($vendorUser);
        $this->getJson('/api/v1/chat/inquiries')->assertOk()->assertJsonPath('data.items.0.unread_count', 1);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}")->assertOk()->assertJsonPath('data.unread_count', 1);
        $this->getJson('/api/v1/chat/unread-count')->assertOk()->assertJsonPath('data.total', 1);

        Sanctum::actingAs($bride);
        $this->getJson('/api/v1/chat/inquiries')->assertOk()->assertJsonPath('data.items.0.unread_count', 0);
        $this->getJson('/api/v1/chat/unread-count')->assertOk()->assertJsonPath('data.total', 0);

        // Replying marks the thread as read for the vendor and unread for the bride.
        Sanctum::actingAs($vendorUser);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Hi'])->assertCreated();
        $this->getJson('/api/v1/chat/unread-count')->assertOk()->assertJsonPath('data.total', 0);

        Sanctum::actingAs($bride);
        $this->getJson('/api/v1/chat/unread-count')->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_unread_total_sums_all_threads_of_the_vendor(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        [, $otherVendorProfile] = $this->vendor();
        $this->startThread($this->userWithRole('bride'), $vendorProfile, 'First');
        $this->startThread($this->userWithRole('groom'), $vendorProfile, 'Second');
        $this->startThread($this->userWithRole('bride'), $otherVendorProfile, 'Not for this vendor');

        Sanctum::actingAs($vendorUser);
        $this->getJson('/api/v1/chat/unread-count')->assertOk()->assertJsonPath('data.total', 2);
    }

    public function test_marking_as_read_moves_the_marker_forward_only(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->startThread($bride, $vendorProfile, 'One');
        Sanctum::actingAs($bride);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Two'])->assertCreated();
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Three'])->assertCreated();
        [$first, $second, $third] = ChatMessage::query()->orderBy('id')->pluck('id')->all();

        Event::fake([ChatUnreadUpdated::class]);
        Sanctum::actingAs($vendorUser);

        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", ['last_read_message_id' => $second])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => null,
                'data' => ['inquiry_id' => $inquiry->id, 'unread_count' => 1, 'total' => 1],
                'errors' => null,
            ]);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", ['last_read_message_id' => $third])
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.total', 0);

        // A late request from another device must not bring the badge back.
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", ['last_read_message_id' => $first])
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        Event::assertDispatchedTimes(ChatUnreadUpdated::class, 2);
        Event::assertDispatched(ChatUnreadUpdated::class, fn (ChatUnreadUpdated $event): bool => $event->userId === $vendorUser->id
            && $event->unreadCount === 0
            && $event->total === 0);
        $this->assertDatabaseHas('vendor_inquiries', [
            'id' => $inquiry->id,
            'vendor_last_read_message_id' => $third,
            'vendor_unread_count' => 0,
            'couple_last_read_message_id' => $third,
        ]);
    }

    public function test_sending_broadcasts_absolute_unread_counters_to_the_recipient(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->startThread($bride, $vendorProfile, 'One');

        Event::fake([ChatUnreadUpdated::class]);
        Sanctum::actingAs($bride);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Two'])->assertCreated();

        Event::assertDispatched(ChatUnreadUpdated::class, function (ChatUnreadUpdated $event) use ($vendorUser, $inquiry): bool {
            $this->assertEquals([new PrivateChannel("chat.user.{$vendorUser->id}")], $event->broadcastOn());
            $this->assertSame('chat.unread.updated', $event->broadcastAs());
            $this->assertSame(['total' => 2, 'inquiry_id' => $inquiry->id, 'unread_count' => 2], $event->broadcastWith());

            return true;
        });
    }

    public function test_mark_read_requires_participant_and_a_message_of_the_thread(): void
    {
        $this->postJson('/api/v1/chat/inquiries/1/read', ['last_read_message_id' => 1])->assertUnauthorized();
        $this->getJson('/api/v1/chat/unread-count')->assertUnauthorized();

        [, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->startThread($bride, $vendorProfile, 'Mine');
        $otherInquiry = $this->startThread($this->userWithRole('groom'), $vendorProfile, 'Other thread');
        $foreignMessageId = ChatMessage::query()->where('vendor_inquiry_id', $otherInquiry->id)->value('id');
        $ownMessageId = ChatMessage::query()->where('vendor_inquiry_id', $inquiry->id)->value('id');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/chat/unread-count')->assertForbidden();

        Sanctum::actingAs($bride);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_read_message_id']);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", ['last_read_message_id' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_read_message_id']);
        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/read", ['last_read_message_id' => $foreignMessageId])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_read_message_id']);
        $this->postJson("/api/v1/chat/inquiries/{$otherInquiry->id}/read", ['last_read_message_id' => $foreignMessageId])
            ->assertForbidden();
        $this->postJson('/api/v1/chat/inquiries/999999/read', ['last_read_message_id' => $ownMessageId])
            ->assertNotFound();
    }

    private function startThread(User $couple, VendorProfile $vendor, string $body): VendorInquiry
    {
        Sanctum::actingAs($couple);
        $id = $this->postJson("/api/v1/vendors/{$vendor->id}/inquiries", ['body' => $body])->assertCreated()->json('data.id');

        return VendorInquiry::query()->findOrFail($id);
    }
}
