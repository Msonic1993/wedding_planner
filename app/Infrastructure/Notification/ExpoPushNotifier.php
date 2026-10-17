<?php

namespace App\Infrastructure\Notification;

use App\Application\Notification\Contracts\PushNotifier;
use App\Application\Notification\Data\PushNotification;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

/**
 * Expo Push API client: https://docs.expo.dev/push-notifications/sending-notifications/
 * Expo accepts at most 100 messages per request and returns one ticket per message, in order.
 */
class ExpoPushNotifier implements PushNotifier
{
    private const int CHUNK_SIZE = 100;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $url,
        private readonly ?string $accessToken,
    ) {}

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     *
     * @throws RequestException when Expo rejects the whole request; the queued job retries it
     */
    public function send(array $tokens, PushNotification $notification): array
    {
        $unregistered = [];

        foreach (array_chunk($tokens, self::CHUNK_SIZE) as $chunk) {
            $messages = array_map(fn (string $token): array => $this->message($token, $notification), $chunk);

            $request = $this->http->acceptJson()->asJson()->timeout(10);
            if ($this->accessToken !== null && $this->accessToken !== '') {
                $request = $request->withToken($this->accessToken);
            }

            /** @var list<array{status?: string, details?: array{error?: string}}> $tickets */
            $tickets = $request->post($this->url, $messages)->throw()->json('data', []);

            foreach ($tickets as $index => $ticket) {
                if (($ticket['status'] ?? null) === 'error' && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                    $unregistered[] = $chunk[$index];
                }
            }
        }

        return $unregistered;
    }

    /** @return array<string, mixed> */
    private function message(string $token, PushNotification $notification): array
    {
        return array_filter([
            'to' => $token,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data,
            'badge' => $notification->badge,
            'sound' => 'default',
            'priority' => 'high',
        ], fn (mixed $value): bool => $value !== null);
    }
}
