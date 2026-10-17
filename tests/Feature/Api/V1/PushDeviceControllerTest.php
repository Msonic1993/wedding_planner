<?php

namespace Tests\Feature\Api\V1;

use App\Application\Chat\Jobs\SendChatMessagePush;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesChatParticipants;
use Tests\TestCase;

class PushDeviceControllerTest extends TestCase
{
    use CreatesChatParticipants;
    use RefreshDatabase;

    private const string TOKEN = 'ExponentPushToken[abc123_-XYZ]';

    private const string EXPO_URL = 'https://exp.host/--/api/v2/push/send';

    public function test_user_registers_device_idempotently(): void
    {
        $bride = $this->userWithRole('bride');
        Sanctum::actingAs($bride);

        $this->postJson('/api/v1/devices', ['token' => self::TOKEN, 'platform' => 'ios'])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Device has been registered.',
                'data' => ['token' => self::TOKEN, 'platform' => 'ios'],
                'errors' => null,
            ]);
        $this->postJson('/api/v1/devices', ['token' => self::TOKEN, 'platform' => 'android'])->assertOk();

        $this->assertDatabaseCount('push_devices', 1);
        $this->assertDatabaseHas('push_devices', ['user_id' => $bride->id, 'token' => self::TOKEN, 'platform' => 'android']);
    }

    public function test_token_moves_to_the_user_who_logged_in_on_the_device(): void
    {
        $bride = $this->userWithRole('bride');
        [$vendorUser] = $this->vendor();
        $bride->pushDevices()->create(['token' => self::TOKEN, 'platform' => 'ios']);

        Sanctum::actingAs($vendorUser);
        $this->postJson('/api/v1/devices', ['token' => self::TOKEN, 'platform' => 'ios'])->assertOk();

        $this->assertDatabaseCount('push_devices', 1);
        $this->assertDatabaseHas('push_devices', ['user_id' => $vendorUser->id, 'token' => self::TOKEN]);
    }

    public function test_register_validates_token_and_platform(): void
    {
        Sanctum::actingAs($this->userWithRole('groom'));

        $this->postJson('/api/v1/devices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'platform']);
        $this->postJson('/api/v1/devices', ['token' => 'not-an-expo-token', 'platform' => 'web'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'platform']);
        $this->assertDatabaseCount('push_devices', 0);
    }

    public function test_device_endpoints_require_authentication_and_role(): void
    {
        $this->postJson('/api/v1/devices', ['token' => self::TOKEN, 'platform' => 'ios'])->assertUnauthorized();
        $this->deleteJson('/api/v1/devices/'.rawurlencode(self::TOKEN))->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/devices', ['token' => self::TOKEN, 'platform' => 'ios'])->assertForbidden();
        $this->deleteJson('/api/v1/devices/'.rawurlencode(self::TOKEN))->assertForbidden();
    }

    public function test_user_removes_only_own_device(): void
    {
        $bride = $this->userWithRole('bride');
        $groom = $this->userWithRole('groom');
        $bride->pushDevices()->create(['token' => self::TOKEN, 'platform' => 'ios']);

        Sanctum::actingAs($groom);
        $this->deleteJson('/api/v1/devices/'.rawurlencode(self::TOKEN))->assertNotFound();
        $this->assertDatabaseCount('push_devices', 1);

        Sanctum::actingAs($bride);
        $this->deleteJson('/api/v1/devices/'.rawurlencode(self::TOKEN))
            ->assertOk()
            ->assertJsonPath('message', 'Device has been removed.');
        $this->assertDatabaseCount('push_devices', 0);
        $this->deleteJson('/api/v1/devices/'.rawurlencode(self::TOKEN))->assertNotFound();
    }

    public function test_sending_a_message_queues_push_for_the_recipient(): void
    {
        [$vendorUser, $vendorProfile] = $this->vendor();
        $bride = $this->userWithRole('bride');
        Bus::fake([SendChatMessagePush::class]);

        Sanctum::actingAs($bride);
        $inquiryId = $this->postJson("/api/v1/vendors/{$vendorProfile->id}/inquiries", ['body' => 'Is 12 June free?'])
            ->assertCreated()
            ->json('data.id');

        Bus::assertDispatched(SendChatMessagePush::class, fn (SendChatMessagePush $job): bool => $job->recipientId === $vendorUser->id
            && $job->inquiryId === $inquiryId
            && $job->unreadTotal === 1);
    }

    public function test_push_job_sends_to_expo_and_prunes_unregistered_tokens(): void
    {
        $vendorUser = $this->userWithRole('vendor');
        $staleToken = 'ExponentPushToken[stale]';
        $vendorUser->pushDevices()->create(['token' => self::TOKEN, 'platform' => 'ios']);
        $vendorUser->pushDevices()->create(['token' => $staleToken, 'platform' => 'android']);
        Http::fake([self::EXPO_URL => Http::response(['data' => [
            ['status' => 'ok', 'id' => 'ticket-1'],
            ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']],
        ]])]);

        SendChatMessagePush::dispatchSync($vendorUser->id, 31, 77, 'Anna', "Hello\n\n  there", 3);

        Http::assertSent(function (Request $request) use ($staleToken): bool {
            $messages = $request->data();

            return $request->url() === self::EXPO_URL
                && $messages[0]['to'] === self::TOKEN
                && $messages[1]['to'] === $staleToken
                && $messages[0]['title'] === 'Anna'
                && $messages[0]['body'] === 'Hello there'
                && $messages[0]['badge'] === 3
                && $messages[0]['data'] === ['type' => 'chat.message', 'inquiry_id' => 31, 'message_id' => 77];
        });
        $this->assertDatabaseHas('push_devices', ['token' => self::TOKEN]);
        $this->assertDatabaseMissing('push_devices', ['token' => $staleToken]);
    }

    public function test_push_job_skips_users_without_devices(): void
    {
        Http::fake();

        SendChatMessagePush::dispatchSync($this->userWithRole('bride')->id, 31, 77, 'Studio Foto', 'Hi', 1);

        Http::assertNothingSent();
    }

    public function test_expo_failure_fails_the_job_for_retry(): void
    {
        $bride = $this->userWithRole('bride');
        $bride->pushDevices()->create(['token' => self::TOKEN, 'platform' => 'ios']);
        Http::fake([self::EXPO_URL => Http::response(['errors' => [['code' => 'INTERNAL']]], 500)]);

        $this->expectException(RequestException::class);

        SendChatMessagePush::dispatchSync($bride->id, 31, 77, 'Studio Foto', 'Hi', 1);
    }
}
