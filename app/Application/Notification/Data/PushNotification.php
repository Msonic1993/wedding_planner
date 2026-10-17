<?php

namespace App\Application\Notification\Data;

final readonly class PushNotification
{
    /** @param  array<string, int|string>  $data  Payload delivered to the app; used for navigation on tap. */
    public function __construct(
        public string $title,
        public string $body,
        public array $data,
        public ?int $badge = null,
    ) {}
}
