<?php

namespace Tests\Feature\Api\V1;

use App\Application\Chat\Events\ChatMessageSent;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
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

    public function test_creating_an_inquiry_broadcasts_the_first_message_and_initializes_activity(): void
    {
        Event::fake([ChatMessageSent::class]);
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        Sanctum::actingAs($bride);

        $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Hello'])
            ->assertCreated()
            ->assertJsonPath('data.messages_count', 1)
            ->assertJsonPath('data.last_message_at', fn (?string $value): bool => $value !== null);

        $inquiry = VendorInquiry::query()->firstOrFail();
        Event::assertDispatched(ChatMessageSent::class, fn (ChatMessageSent $event): bool => $event->inquiryId === $inquiry->id
            && $event->participantIds === [$bride->id, $vendorUser->id]
            && $event->message['body'] === 'Hello');
    }

    public function test_sending_a_message_broadcasts_to_thread_and_participant_channels(): void
    {
        Event::fake([ChatMessageSent::class]);
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);
        Sanctum::actingAs($vendorUser);

        $messageId = $this->withHeader('X-Socket-ID', '1234.5678')
            ->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Yes, available.'])
            ->assertCreated()
            ->json('data.id');

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($bride, $inquiry, $messageId, $vendorUser): bool {
            $channels = array_map(fn (PrivateChannel $channel): string => $channel->name, $event->broadcastOn());

            return $channels === ["private-chat.inquiry.{$inquiry->id}", "private-chat.user.{$bride->id}", "private-chat.user.{$vendorUser->id}"]
                && $event->broadcastAs() === 'chat.message.sent'
                && $event->socket === '1234.5678'
                && $event->broadcastWith() === [
                    'inquiry_id' => $inquiry->id,
                    'message' => [
                        'id' => $messageId,
                        'body' => 'Yes, available.',
                        'sender' => ['id' => $vendorUser->id, 'name' => $vendorUser->name],
                        'created_at' => $event->message['created_at'],
                    ],
                ];
        });

        $inquiry->refresh();
        $this->assertSame(1, $inquiry->messages_count);
        $this->assertSame($messageId, $inquiry->last_message_id);
        $this->assertNotNull($inquiry->last_message_at);
    }

    public function test_message_rejected_for_non_participant_is_not_broadcast(): void
    {
        Event::fake([ChatMessageSent::class]);
        [, $vendorProfile] = $this->vendor();
        $inquiry = $this->createInquiry($this->userWithRole('bride'), $vendorProfile);
        Sanctum::actingAs($this->userWithRole('groom'));

        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'Hi'])->assertForbidden();
        $this->postJson('/api/v1/chat/inquiries/999999/messages', ['body' => 'Hi'])->assertNotFound();

        Event::assertNotDispatched(ChatMessageSent::class);
        $this->assertSame(0, $inquiry->refresh()->messages_count);
    }

    public function test_inquiry_list_is_sorted_by_latest_message_activity(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $firstInquiry = $this->createInquiry($this->userWithRole('bride'), $vendorProfile);
        $secondInquiry = $this->createInquiry($this->userWithRole('groom'), $vendorProfile);
        Sanctum::actingAs($vendorUser);

        $this->travel(1)->minutes();
        $this->postJson("/api/v1/chat/inquiries/{$secondInquiry->id}/messages", ['body' => 'Second'])->assertCreated();
        $this->travel(1)->minutes();
        $this->postJson("/api/v1/chat/inquiries/{$firstInquiry->id}/messages", ['body' => 'First'])->assertCreated();

        $this->getJson('/api/v1/chat/inquiries')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $firstInquiry->id)
            ->assertJsonPath('data.items.0.messages_count', 1)
            ->assertJsonPath('data.items.1.id', $secondInquiry->id);
    }

    public function test_cursor_mode_returns_keyset_windows_oldest_first(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);
        Sanctum::actingAs($vendorUser);
        $ids = [];
        foreach (range(1, 5) as $number) {
            $ids[] = $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => "Message {$number}"])->json('data.id');
        }

        Sanctum::actingAs($bride);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages?before_id={$ids[4]}&per_page=2")
            ->assertOk()
            ->assertJsonPath('data.items.0.body', 'Message 3')
            ->assertJsonPath('data.items.1.body', 'Message 4')
            ->assertJsonPath('data.cursor.has_more', true)
            ->assertJsonPath('data.cursor.per_page', 2)
            ->assertJsonMissingPath('data.pagination');
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages?before_id={$ids[2]}&per_page=2")
            ->assertOk()
            ->assertJsonPath('data.items.0.body', 'Message 1')
            ->assertJsonPath('data.cursor.has_more', false);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages?after_id={$ids[1]}&per_page=2")
            ->assertOk()
            ->assertJsonPath('data.items.0.body', 'Message 3')
            ->assertJsonPath('data.items.1.body', 'Message 4')
            ->assertJsonPath('data.cursor.has_more', true);
        $this->getJson("/api/v1/chat/inquiries/{$inquiry->id}/messages?after_id={$ids[4]}")
            ->assertOk()
            ->assertJsonCount(0, 'data.items')
            ->assertJsonPath('data.cursor.has_more', false);
    }

    public function test_cursor_parameters_are_validated_and_authorized(): void
    {
        [, $vendorProfile] = $this->vendor();
        $inquiry = $this->createInquiry($this->userWithRole('bride'), $vendorProfile);
        $url = "/api/v1/chat/inquiries/{$inquiry->id}/messages";

        $this->getJson("{$url}?after_id=1")->assertUnauthorized();

        Sanctum::actingAs($this->userWithRole('groom'));
        $this->getJson("{$url}?after_id=1")->assertForbidden();
        $this->getJson('/api/v1/chat/inquiries/999999/messages?after_id=1')->assertNotFound();
        $this->getJson("{$url}?before_id=0")->assertUnprocessable()->assertJsonValidationErrors(['before_id']);
        $this->getJson("{$url}?before_id=5&after_id=1")->assertUnprocessable()->assertJsonValidationErrors(['before_id', 'after_id']);
        $this->getJson("{$url}?page=2&after_id=1")->assertUnprocessable()->assertJsonValidationErrors(['after_id']);
        $this->getJson("{$url}?after_id=abc")->assertUnprocessable()->assertJsonValidationErrors(['after_id']);
    }

    public function test_private_channels_are_authorized_only_for_participants(): void
    {
        $this->useReverbBroadcaster();
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $otherBride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);
        $auth = fn (string $channel) => $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ]);

        $auth("private-chat.inquiry.{$inquiry->id}")->assertUnauthorized();

        Sanctum::actingAs($bride);
        $auth("private-chat.inquiry.{$inquiry->id}")->assertOk()->assertJsonStructure(['auth']);
        $auth("private-chat.user.{$bride->id}")->assertOk()->assertJsonStructure(['auth']);
        $auth("private-chat.user.{$vendorUser->id}")->assertForbidden();

        Sanctum::actingAs($vendorUser);
        $auth("private-chat.inquiry.{$inquiry->id}")->assertOk();

        Sanctum::actingAs($otherBride);
        $auth("private-chat.inquiry.{$inquiry->id}")->assertForbidden();
        $auth('private-chat.inquiry.999999')->assertForbidden();
    }

    public function test_sending_messages_is_rate_limited_per_user(): void
    {
        Event::fake([ChatMessageSent::class]);
        [, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        $inquiry = $this->createInquiry($bride, $vendorProfile);
        Sanctum::actingAs($bride);

        foreach (range(1, 30) as $number) {
            $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => "Message {$number}"])->assertCreated();
        }

        $this->postJson("/api/v1/chat/inquiries/{$inquiry->id}/messages", ['body' => 'One too many'])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
        $this->assertSame(30, $inquiry->refresh()->messages_count);
    }

    private function useReverbBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
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
